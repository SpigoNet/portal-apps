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
    public function index()
    {
        $maps = DspaceFormMap::orderBy('map_key')->get();

        // Pega apenas os nomes dos processos de submissão para os dropdowns
        $submission_processes = SubmissionProcess::orderBy('name')->pluck('name');

        return view('DspaceForms::form-maps-index', compact('maps', 'submission_processes'));
    }

    /**
     * Armazena um novo vínculo no banco de dados.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'map_type' => 'required|in:handle,entity-type',
            'map_key' => [
                'required',
                'string',
                'max:255',
                // Garante que a combinação map_key e map_type seja única
                Rule::unique('dspace_form_maps')->where(function ($query) use ($request) {
                    return $query->where('map_type', $request->map_type);
                }),
            ],
            'submission_name' => 'required|string|exists:submission_processes,name',
        ]);

        DspaceFormMap::create($validated);

        return redirect()->route('dspace-forms.form-maps.index')->with('success', 'Vínculo criado com sucesso.');
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
