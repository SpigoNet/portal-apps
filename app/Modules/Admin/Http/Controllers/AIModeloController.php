<?php
namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AIProvedor;
use App\Modules\Admin\Models\AIModelo;
use App\Modules\Admin\Models\AIModeloPadrao;
use Illuminate\Http\Request;

class AIModeloController extends Controller
{
    public function index(AIProvedor $provedor)
    {
        $modelos = $provedor->modelos()->with('e_padrao')->get();
        $padroes = AIModeloPadrao::all()->keyBy(function($item) {
            return $item->input_type . '->' . $item->output_type;
        });

        return view('Admin::ai.modelos.index', compact('provedor', 'modelos', 'padroes'));
    }

    public function toggle(AIModelo $modelo)
    {
        $modelo->update(['is_active' => !$modelo->is_active]);
        return back()->with('success', 'Status do modelo atualizado!');
    }

    public function setDefault(Request $request, AIModelo $modelo)
    {
        $validated = $request->validate([
            'input_type' => 'required|string',
            'output_type' => 'required|string',
        ]);

        AIModeloPadrao::updateOrCreate(
            ['input_type' => $validated['input_type'], 'output_type' => $validated['output_type']],
            ['ai_modelo_id' => $modelo->id]
        );

        return back()->with('success', 'Modelo definido como padrão para ' . $validated['input_type'] . '->' . $validated['output_type']);
    }
}
