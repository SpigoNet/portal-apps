<?php

namespace App\Modules\ComfyQueue\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ComfyQueue\Models\JobModel;
use Illuminate\Http\Request;

class JobModelController extends Controller
{
    public function index()
    {
        $modelos = JobModel::orderBy('nome')->get();

        return view('ComfyQueue::job-models.index', compact('modelos'));
    }

    public function create()
    {
        return view('ComfyQueue::job-models.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'json' => 'required|string',
        ]);

        $jsonDecoded = json_decode($validated['json'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['json' => 'JSON inválido: ' . json_last_error_msg()])->withInput();
        }

        JobModel::create([
            'nome' => $validated['nome'],
            'json' => $jsonDecoded,
        ]);

        return redirect()->route('comfy-queue.job-models.index')
            ->with('success', 'Modelo criado com sucesso.');
    }

    public function edit(int $id)
    {
        $modelo = JobModel::findOrFail($id);

        return view('ComfyQueue::job-models.edit', compact('modelo'));
    }

    public function update(Request $request, int $id)
    {
        $modelo = JobModel::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'json' => 'required|string',
        ]);

        $jsonDecoded = json_decode($validated['json'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['json' => 'JSON inválido: ' . json_last_error_msg()])->withInput();
        }

        $modelo->update([
            'nome' => $validated['nome'],
            'json' => $jsonDecoded,
        ]);

        return redirect()->route('comfy-queue.job-models.index')
            ->with('success', 'Modelo atualizado com sucesso.');
    }

    public function destroy(int $id)
    {
        $modelo = JobModel::findOrFail($id);
        $modelo->delete();

        return redirect()->route('comfy-queue.job-models.index')
            ->with('success', 'Modelo removido com sucesso.');
    }
}