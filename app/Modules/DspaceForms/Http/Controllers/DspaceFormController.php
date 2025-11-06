<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class DspaceFormController extends Controller
{
    /**
     * Display a listing of the resource (DspaceForms).
     */
    public function index()
    {
        $forms = DspaceForm::orderBy('name')->get();
        return view('DspaceForms::forms.index', compact('forms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('DspaceForms::forms.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:dspace_forms,name',
        ]);

        DspaceForm::create($validated);

        return Redirect::route('dspace-forms.forms.index')->with('success', 'Formulário criado com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     * Este é o ponto de entrada para o editor de linhas e campos.
     */
    public function edit(DspaceForm $form)
    {
        // Carrega as linhas e campos relacionados para o editor
        $form->load('rows.fields', 'rows.relationFields');
        return view('DspaceForms::forms.edit', compact('form'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DspaceForm $form)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:dspace_forms,name,' . $form->id,
        ]);

        $form->update($validated);

        return Redirect::route('dspace-forms.forms.index')->with('success', 'Formulário atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DspaceForm $form)
    {
        $form->delete();
        return Redirect::route('dspace-forms.forms.index')->with('success', 'Formulário excluído com sucesso.');
    }
}
