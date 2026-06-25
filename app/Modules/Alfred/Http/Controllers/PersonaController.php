<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Alfred\Services\EvolutionApiService;
use Illuminate\Http\Request;

class PersonaController
{
    public function index()
    {
        return view('Alfred::admin.personas.index', ['personas' => Persona::all()]);
    }

    public function show(Persona $persona)
    {
        return view('Alfred::admin.personas.show', ['persona' => $persona]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:alfred_personas,slug',
            'whatsapp_group_jid' => 'nullable|string',
            'personality' => 'nullable|string',
            'metadata' => 'nullable|string',
        ]);

        if (! empty($data['personality'])) {
            $decoded = json_decode($data['personality'], true);
            $data['personality'] = is_array($decoded) ? $decoded : null;
        }

        if (! empty($data['metadata'])) {
            $decoded = json_decode($data['metadata'], true);
            $data['metadata'] = is_array($decoded) ? $decoded : null;
        }

        if (! empty($data['whatsapp_group_jid'])) {
            $data['whatsapp_group_jid'] = $this->normalizeGroupJid($data['whatsapp_group_jid']);
        }

        $persona = Persona::create($data);

        return redirect()->route('alfred.admin.personas.index')->with('success', 'Persona criada');
    }

    public function edit(Persona $persona)
    {
        return view('Alfred::admin.personas.edit', ['persona' => $persona]);
    }

    public function update(Request $request, Persona $persona)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:alfred_personas,slug,'.$persona->id,
            'whatsapp_group_jid' => 'nullable|string',
            'personality' => 'nullable|string',
            'metadata' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        if (array_key_exists('personality', $data) && ! empty($data['personality'])) {
            $decoded = json_decode($data['personality'], true);
            $data['personality'] = is_array($decoded) ? $decoded : null;
        } else {
            $data['personality'] = null;
        }

        if (array_key_exists('metadata', $data) && ! empty($data['metadata'])) {
            $decoded = json_decode($data['metadata'], true);
            $data['metadata'] = is_array($decoded) ? $decoded : null;
        } else {
            $data['metadata'] = null;
        }

        $data['active'] = isset($data['active']) ? (bool) $data['active'] : false;

        if (array_key_exists('whatsapp_group_jid', $data) && ! empty($data['whatsapp_group_jid'])) {
            $data['whatsapp_group_jid'] = $this->normalizeGroupJid($data['whatsapp_group_jid']);
        }

        $persona->update($data);

        return redirect()->route('alfred.admin.personas.index')->with('success', 'Persona atualizada');
    }

    public function destroy(Persona $persona)
    {
        $persona->delete();

        return redirect()->route('alfred.admin.personas.index')->with('success', 'Persona removida');
    }

    private function normalizeGroupJid(?string $jid): ?string
    {
        if (empty($jid)) {
            return null;
        }

        $clean = trim($jid);
        if (strpos($clean, '@') !== false) {
            return $clean;
        }

        $digits = preg_replace('/[^0-9]/', '', $clean);
        if ($digits !== '') {
            return $digits.'@g.us';
        }

        return $clean;
    }

    public function sendTestMessage(Persona $persona, EvolutionApiService $evo)
    {
        $message = ($persona->personality['greetings'][0] ?? "Oi, sou {$persona->name}!");

        $result = $evo->sendTextToGroup($persona->whatsapp_group_jid ?? '', $message);

        if (is_array($result)) {
            if ($result['ok']) {
                return redirect()->back()->with('success', 'Mensagem enviada (status '.$result['status'].')');
            }

            $details = 'Erro: '.($result['error'] ?? 'unknown').' | status: '.($result['status'] ?? 'n/a').' | body: '.($result['body'] ?? 'n/a');

            return redirect()->back()->with('error', 'Falha ao enviar mensagem: '.config('services.evolution.base_uri', '').' '.$details);
        }

        return redirect()->back()->with('error', 'Falha inesperada ao enviar a mensagem');
    }
}
