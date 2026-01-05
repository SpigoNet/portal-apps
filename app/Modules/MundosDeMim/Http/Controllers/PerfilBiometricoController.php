<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\UserAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Necessário para gerenciar arquivos

class PerfilBiometricoController extends Controller
{
    public function index()
    {
        $attributes = UserAttribute::where('user_id', Auth::id())->first();
        return view('MundosDeMim::perfil.index', compact('attributes'));
    }

    public function update(Request $request)
    {
        // 1. Validação
        $validated = $request->validate([
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
            'height'    => 'nullable|numeric|min:50|max:250',
            'weight'    => 'nullable|numeric|min:20|max:300',
            'body_type' => 'required|string|max:50',
            'eye_color' => 'required|string|max:50',
            'hair_type' => 'required|string|max:50',
        ]);

        // 2. Busca ou inicia o objeto
        $attributes = UserAttribute::firstOrNew(['user_id' => Auth::id()]);

        // 3. Lógica de Upload da Foto
        if ($request->hasFile('photo')) {
            // Se já existir uma foto anterior, deletar para não acumular lixo
            if ($attributes->photo_path && Storage::exists($attributes->photo_path)) {
                Storage::delete($attributes->photo_path);
            }

            // Salva na pasta 'user_references' (pode ser disco 'public' ou 's3')
            $path = $request->file('photo')->store('user_references', 'public');
            $attributes->photo_path = $path;
        }

        // 4. Preenche os demais dados
        $attributes->height = $validated['height'];
        $attributes->weight = $validated['weight'];
        $attributes->body_type = $validated['body_type'];
        $attributes->eye_color = $validated['eye_color'];
        $attributes->hair_type = $validated['hair_type'];

        $attributes->save();

        return redirect()->route('mundos-de-mim.perfil.index')
            ->with('success', 'Perfil biométrico e foto atualizados com sucesso.');
    }
}
