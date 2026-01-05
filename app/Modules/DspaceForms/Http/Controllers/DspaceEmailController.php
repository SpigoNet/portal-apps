<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceEmailTemplate;
use App\Modules\DspaceForms\Models\DspaceXmlConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DspaceEmailController extends Controller
{
    public function index(Request $request)
    {
        $configId = $request->query('config_id');
        if (!$configId) {
            return redirect()->route('dspace-forms.index')->with('error', 'Configuração não especificada.');
        }

        $currentConfig = DspaceXmlConfiguration::findOrFail($configId);
        if ($currentConfig->user_id !== Auth::id()) abort(403);

        $templates = DspaceEmailTemplate::where('xml_configuration_id', $configId)
            ->orderBy('name')
            ->get();

        return view('DspaceForms::emails.index', compact('templates', 'currentConfig'));
    }

    public function edit(DspaceEmailTemplate $template)
    {
        if ($template->configuration->user_id !== Auth::id()) abort(403);

        return view('DspaceForms::emails.edit', compact('template'));
    }

    public function update(Request $request, DspaceEmailTemplate $template)
    {
        if ($template->configuration->user_id !== Auth::id()) abort(403);

        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        $template->update($validated);

        return redirect()->route('dspace-forms.emails.index', ['config_id' => $template->xml_configuration_id])
            ->with('success', "Template '{$template->name}' atualizado com sucesso.");
    }
}
