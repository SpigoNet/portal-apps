<?php
namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AIProvedor;
use App\Modules\Admin\Models\AIModelo;
use App\Modules\Admin\Models\AIModeloPadrao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIProvedorController extends Controller
{
    public function index()
    {
        $provedores = AIProvedor::withCount('modelos')->get();
        $modelosPadrao = AIModeloPadrao::with('modelo.provedor')->get();
        return view('Admin::ai.index', compact('provedores', 'modelosPadrao'));
    }

    public function create()
    {
        return view('Admin::ai.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'url_json_modelos' => 'nullable|url',
            'default_input_types' => 'nullable|array',
            'default_output_types' => 'nullable|array',
        ]);

        AIProvedor::create($validated);

        return redirect()->route('admin.ai.provedores.index')->with('success', 'Provedor criado com sucesso!');
    }

    public function edit(AIProvedor $provedor)
    {
        return view('Admin::ai.edit', compact('provedor'));
    }

    public function update(Request $request, AIProvedor $provedor)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'url_json_modelos' => 'nullable|url',
            'default_input_types' => 'nullable|array',
            'default_output_types' => 'nullable|array',
        ]);

        $provedor->update($validated);

        return redirect()->route('admin.ai.provedores.index')->with('success', 'Provedor atualizado com sucesso!');
    }

    public function destroy(AIProvedor $provedor)
    {
        $provedor->delete();
        return redirect()->route('admin.ai.provedores.index')->with('success', 'Provedor removido com sucesso!');
    }

    public function sync(AIProvedor $provedor)
    {
        if (!$provedor->url_json_modelos) {
            return back()->with('error', 'URL de sincronização não definida.');
        }

        try {
            $response = Http::get($provedor->url_json_modelos);
            $data = $response->json();

            // Normalização: Alguns retornam lista direta, outros dentro de 'data'
            $modelsList = isset($data['data']) ? $data['data'] : $data;

            foreach ($modelsList as $item) {
                $this->processModel($provedor, $item);
            }

            return back()->with('success', 'Modelos sincronizados com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Falha ao sincronizar: ' . $e->getMessage());
        }
    }

    private function processModel(AIProvedor $provedor, array $item)
    {
        $externalId = $item['id'] ?? $item['name'] ?? null;
        if (!$externalId) return;

        // 1. Tratar tipos de entrada/saída (Pollinations vs Airforce)
        $input = $item['input_modalities'] ?? ($item['supports_chat'] ?? false ? ['text'] : ($provedor->default_input_types ?? ['text']));
        $output = $item['output_modalities'] ?? ($item['supports_images'] ?? false ? ['image'] : ($provedor->default_output_types ?? ['text']));

        if ($item['supports_images'] ?? false) {
            $input = array_unique(array_merge((array)$input, ['text']));
            $output = array_unique(array_merge((array)$output, ['image']));
        }

        // 2. Tratar Pricing (Converter Notação Científica)
        $pricing = null;
        if (isset($item['pricing'])) {
            $pricing = collect($item['pricing'])->map(function ($value) {
                return is_numeric($value) ? number_format((float)$value, 15, '.', '') : $value;
            })->toArray();
        }

        // 3. Persistir
        AIModelo::updateOrCreate(
            ['ai_provedor_id' => $provedor->id, 'modelo_id_externo' => $externalId],
            [
                'nome' => $item['name'] ?? $item['id'],
                'descricao' => $item['description'] ?? ($item['owned_by'] ?? ''),
                'input_types' => (array)$input,
                'output_types' => (array)$output,
                'pricing' => $pricing,
                'raw_data' => $item // Salva tudo conforme solicitado
            ]
        );
    }
}
