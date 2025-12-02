<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceForm;
use App\Modules\DspaceForms\Models\DspaceXmlConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class DspaceFormController extends Controller
{
    /**
     * Display a listing of the resource (DspaceForms).
     * Agora filtra pelo XML Configuration atual.
     */
    public function index(Request $request)
    {
        $configId = $request->query('config_id');

        if (!$configId) {
            return redirect()->route('dspace-forms.index')
                ->with('error', 'Selecione uma configuração para visualizar os formulários.');
        }

        $currentConfig = DspaceXmlConfiguration::findOrFail($configId);

        // Verifica permissão (se necessário)
        if ($currentConfig->user_id !== auth()->id()) {
            abort(403);
        }

        $forms = DspaceForm::where('xml_configuration_id', $configId)
            ->orderBy('name')
            ->get();

        return view('DspaceForms::forms.index', compact('forms', 'currentConfig'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $configId = $request->query('config_id');
        if (!$configId) {
            return redirect()->route('dspace-forms.index');
        }

        $currentConfig = DspaceXmlConfiguration::findOrFail($configId);

        return view('DspaceForms::forms.create', compact('currentConfig'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $configId = $request->input('xml_configuration_id');

        // Garante que a configuração existe e pertence ao usuário
        $config = DspaceXmlConfiguration::where('id', $configId)->where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Nome único APENAS dentro desta configuração
                Rule::unique('dspace_forms')->where(function ($query) use ($configId) {
                    return $query->where('xml_configuration_id', $configId);
                }),
            ],
            'xml_configuration_id' => 'required|exists:dspace_xml_configurations,id',
        ]);

        DspaceForm::create($validated);

        return Redirect::route('dspace-forms.forms.index', ['config_id' => $configId])
            ->with('success', 'Formulário criado com sucesso na configuração: ' . $config->name);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DspaceForm $form)
    {
        $form->load('rows.fields', 'rows.relationFields');
        // Recupera a config para manter o contexto nos links de "Voltar"
        $currentConfig = DspaceXmlConfiguration::find($form->xml_configuration_id);

        return view('DspaceForms::forms.edit', compact('form', 'currentConfig'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DspaceForm $form)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Único na configuração, ignorando o próprio ID
                Rule::unique('dspace_forms')
                    ->where('xml_configuration_id', $form->xml_configuration_id)
                    ->ignore($form->id),
            ],
        ]);

        $form->update($validated);

        return Redirect::route('dspace-forms.forms.index', ['config_id' => $form->xml_configuration_id])
            ->with('success', 'Formulário atualizado com sucesso.');
    }

    public function destroy(DspaceForm $form)
    {
        $configId = $form->xml_configuration_id;
        $form->delete();
        return Redirect::route('dspace-forms.forms.index', ['config_id' => $configId])
            ->with('success', 'Formulário excluído com sucesso.');
    }
}
