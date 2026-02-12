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
    use DspaceConfigSession;

    public function index(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $forms = DspaceForm::where('xml_configuration_id', $config->id)
            ->orderBy('name')
            ->get();

        return view('DspaceForms::forms.index', compact('forms', 'config'));
    }

    public function create(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        return view('DspaceForms::forms.create', compact('config'));
    }

    public function store(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dspace_forms')->where(function ($query) use ($config) {
                    return $query->where('xml_configuration_id', $config->id);
                }),
            ],
        ]);

        DspaceForm::create([
            'name' => $validated['name'],
            'xml_configuration_id' => $config->id,
        ]);

        return Redirect::route('dspace-forms.forms.index')
            ->with('success', 'Formulário criado com sucesso na configuração: '.$config->name);
    }

    public function edit(DspaceForm $form)
    {
        $form->load('rows.fields', 'rows.relationFields');
        $config = DspaceXmlConfiguration::find($form->xml_configuration_id);

        return view('DspaceForms::forms.edit', compact('form', 'config'));
    }

    public function update(Request $request, DspaceForm $form)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dspace_forms')
                    ->where('xml_configuration_id', $form->xml_configuration_id)
                    ->ignore($form->id),
            ],
        ]);

        $form->update($validated);

        return Redirect::route('dspace-forms.forms.index')
            ->with('success', 'Formulário atualizado com sucesso.');
    }

    public function destroy(DspaceForm $form)
    {
        $form->delete();

        return Redirect::route('dspace-forms.forms.index')
            ->with('success', 'Formulário excluído com sucesso.');
    }
}
