<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DspaceEmailController extends Controller
{
    use DspaceConfigSession;

    public function index(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $templates = DspaceEmailTemplate::where('xml_configuration_id', $config->id)
            ->orderBy('name')
            ->get();

        return view('DspaceForms::emails.index', compact('templates', 'config'));
    }

    public function edit(DspaceEmailTemplate $template)
    {
        if ($template->configuration->user_id !== Auth::id()) {
            abort(403);
        }

        return view('DspaceForms::emails.edit', compact('template'));
    }

    public function update(Request $request, DspaceEmailTemplate $template)
    {
        if ($template->configuration->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        $template->update($validated);

        return redirect()->route('dspace-forms.emails.index')
            ->with('success', "Template '{$template->name}' atualizado com sucesso.");
    }
}
