<?php

namespace App\Modules\MundosDeMim\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\RelatedPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PessoasRelacionadasController extends Controller
{
    public function index()
    {
        $pessoas = RelatedPerson::where('user_id', Auth::id())->get();
        return view('MundosDeMim::pessoas.index', compact('pessoas'));
    }

    public function create()
    {
        return view('MundosDeMim::pessoas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:50',
            'photo' => 'required|image|max:5120', // Max 5MB
        ]);

        // Isolamento de dados conforme segurança [cite: 72]
        $path = $request->file('photo')->store('related_people_photos', 'public');

        RelatedPerson::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'relationship' => $validated['relationship'],
            'photo_path' => $path,
            'is_active' => true,
        ]);

        return redirect()->route('mundos-de-mim.pessoas.index')
            ->with('success', 'Pessoa adicionada! O sistema agora pode gerar fotos em dupla.');
    }

    public function toggleActive($id)
    {
        $pessoa = RelatedPerson::where('user_id', Auth::id())->findOrFail($id);
        $pessoa->is_active = !$pessoa->is_active;
        $pessoa->save();

        return redirect()->back()->with('success', 'Status atualizado.');
    }
}
