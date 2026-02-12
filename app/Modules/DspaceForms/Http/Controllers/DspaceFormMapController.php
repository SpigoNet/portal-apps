<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceFormMap;
use App\Modules\DspaceForms\Models\SubmissionProcess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DspaceFormMapController extends Controller
{
    use DspaceConfigSession;

    public function index(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $maps = DspaceFormMap::where('xml_configuration_id', $config->id)
            ->orderBy('map_key')
            ->get();

        $submission_processes = SubmissionProcess::where('xml_configuration_id', $config->id)
            ->orderBy('name')
            ->pluck('name');

        return view('DspaceForms::form-maps-index', compact('maps', 'submission_processes', 'config'));
    }

    public function store(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $validated = $request->validate([
            'map_type' => 'required|in:handle,entity-type',
            'submission_name' => 'required|string',
            'map_key' => [
                'required', 'string', 'max:255',
                Rule::unique('dspace_form_maps')
                    ->where('map_type', $request->map_type)
                    ->where('xml_configuration_id', $config->id),
            ],
        ]);

        DspaceFormMap::create([
            'map_type' => $validated['map_type'],
            'map_key' => $validated['map_key'],
            'submission_name' => $validated['submission_name'],
            'xml_configuration_id' => $config->id,
        ]);

        return redirect()->route('dspace-forms.form-maps.index')
            ->with('success', 'Vínculo criado com sucesso.');
    }

    public function update(Request $request, DspaceFormMap $map)
    {
        $validated = $request->validate([
            'map_type' => 'required|in:handle,entity-type',
            'map_key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dspace_form_maps')->where(function ($query) use ($request) {
                    return $query->where('map_type', $request->map_type);
                })->ignore($map->id),
            ],
            'submission_name' => 'required|string|exists:submission_processes,name',
        ]);

        $map->update($validated);

        return redirect()->route('dspace-forms.form-maps.index')->with('success', 'Vínculo atualizado com sucesso.');
    }

    public function destroy(DspaceFormMap $map)
    {
        $map->delete();

        return redirect()->route('dspace-forms.form-maps.index')->with('success', 'Vínculo excluído com sucesso.');
    }
}
