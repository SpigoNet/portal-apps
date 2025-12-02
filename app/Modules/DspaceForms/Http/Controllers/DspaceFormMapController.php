<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceFormMap;
use App\Modules\DspaceForms\Models\SubmissionProcess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DspaceFormMapController extends Controller
{
    /**
     * Exibe a lista de todos os mapeamentos (vínculos).
     */
    public function index(Request $request)
    {
        $configId = $request->query('config_id');
        if (!$configId) return redirect()->route('dspace-forms.index');

        $currentConfig = DspaceXmlConfiguration::findOrFail($configId);
        if ($currentConfig->user_id !== auth()->id()) abort(403);

        $maps = DspaceFormMap::where('xml_configuration_id', $configId)
            ->orderBy('map_key')
            ->get();

        // Processos de submissão também devem ser filtrados pela config, se aplicável,
        // ou globais se forem padrão. Assumindo que são da config:
        $submission_processes = SubmissionProcess::where('xml_configuration_id', $configId)
            ->orderBy('name')
            ->pluck('name');

        return view('DspaceForms::form-maps-index', compact('maps', 'submission_processes', 'currentConfig'));
    }

    public function store(Request $request)
    {
        $configId = $request->input('xml_configuration_id');

        $validated = $request->validate([
            'xml_configuration_id' => 'required|exists:dspace_xml_configurations,id',
            'map_type' => 'required|in:handle,entity-type',
            'submission_name' => 'required|string',
            'map_key' => [
                'required', 'string', 'max:255',
                // Único por tipo E por configuração
                Rule::unique('dspace_form_maps')
                    ->where('map_type', $request->map_type)
                    ->where('xml_configuration_id', $configId),
            ],
        ]);

        DspaceFormMap::create($validated);

        return redirect()->route('dspace-forms.form-maps.index', ['config_id' => $configId])
            ->with('success', 'Vínculo criado com sucesso.');
    }

    /**
     * Atualiza um vínculo existente.
     */
    public function update(Request $request, DspaceFormMap $map)
    {
        $validated = $request->validate([
            'map_type' => 'required|in:handle,entity-type',
            'map_key' => [
                'required',
                'string',
                'max:255',
                // Garante que a combinação seja única, ignorando o registro atual
                Rule::unique('dspace_form_maps')->where(function ($query) use ($request) {
                    return $query->where('map_type', $request->map_type);
                })->ignore($map->id),
            ],
            'submission_name' => 'required|string|exists:submission_processes,name',
        ]);

        $map->update($validated);

        return redirect()->route('dspace-forms.form-maps.index')->with('success', 'Vínculo atualizado com sucesso.');
    }

    /**
     * Remove um vínculo.
     */
    public function destroy(DspaceFormMap $map)
    {
        $map->delete();
        return redirect()->route('dspace-forms.form-maps.index')->with('success', 'Vínculo excluído com sucesso.');
    }
}
