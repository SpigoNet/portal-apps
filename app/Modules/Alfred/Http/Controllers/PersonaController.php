<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Alfred\Services\PersonaEnvioService;
use App\Modules\Alfred\Services\TelegramWebhookService;
use Illuminate\Http\Request;

class PersonaController
{
    public function index()
    {
        return view('Alfred::admin.personas.index', ['personas' => Persona::all()]);
    }

    public function create()
    {
        return view('Alfred::admin.personas.create');
    }

    public function show(Persona $persona, TelegramWebhookService $webhook)
    {
        return view('Alfred::admin.personas.show', [
            'persona' => $persona,
            'webhookInfo' => $this->obterInfoWebhook($persona, $webhook),
            'webhookPadrao' => $webhook->endpointPadrao($persona->slug),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:alfred_personas,slug',
            'canal' => 'required|in:whatsapp,telegram',
            'whatsapp_group_jid' => 'nullable|string',
            'telegram_token' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',
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

    public function edit(Persona $persona, TelegramWebhookService $webhook)
    {
        return view('Alfred::admin.personas.edit', [
            'persona' => $persona,
            'webhookInfo' => $this->obterInfoWebhook($persona, $webhook),
            'webhookPadrao' => $webhook->endpointPadrao($persona->slug),
        ]);
    }

    public function update(Request $request, Persona $persona)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:alfred_personas,slug,'.$persona->id,
            'canal' => 'required|in:whatsapp,telegram',
            'whatsapp_group_jid' => 'nullable|string',
            'telegram_token' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',
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

    public function sendTestMessage(Persona $persona, PersonaEnvioService $envio)
    {
        $message = ($persona->personality['greetings'][0] ?? "Oi, sou {$persona->name}!");

        $result = $envio->enviar($persona, $message);

        if (is_array($result)) {
            if ($result['ok']) {
                return redirect()->back()->with('success', 'Mensagem enviada (status '.$result['status'].')');
            }

            $details = 'Erro: '.($result['error'] ?? 'unknown').' | status: '.($result['status'] ?? 'n/a').' | body: '.($result['body'] ?? 'n/a');

            return redirect()->back()->with('error', 'Falha ao enviar mensagem: '.$details);
        }

        return redirect()->back()->with('error', 'Falha inesperada ao enviar a mensagem');
    }

    public function configureWebhook(Request $request, Persona $persona, TelegramWebhookService $webhook)
    {
        if ($persona->canal !== 'telegram' || empty($persona->telegram_token)) {
            return redirect()->back()->with('error', 'A persona precisa usar o canal Telegram e ter um token configurado.');
        }

        $url = $request->input('telegram_webhook_url') ?: $webhook->endpointPadrao($persona->slug);

        $result = $webhook->configurar($persona->telegram_token, $url);

        if ($result['ok']) {
            return redirect()->back()->with('success', 'Webhook do Telegram configurado para: '.$url);
        }

        $details = ($result['error'] ?? 'unknown').' | status: '.($result['status'] ?? 'n/a').' | body: '.($result['body'] ?? 'n/a');

        return redirect()->back()->with('error', 'Falha ao configurar webhook: '.$details);
    }

    public function clearWebhook(Persona $persona, TelegramWebhookService $webhook)
    {
        if (empty($persona->telegram_token)) {
            return redirect()->back()->with('error', 'A persona não possui token do Telegram configurado.');
        }

        $result = $webhook->remover($persona->telegram_token);

        if ($result['ok']) {
            return redirect()->back()->with('success', 'Webhook do Telegram removido.');
        }

        $details = ($result['error'] ?? 'unknown').' | status: '.($result['status'] ?? 'n/a').' | body: '.($result['body'] ?? 'n/a');

        return redirect()->back()->with('error', 'Falha ao remover webhook: '.$details);
    }

    private function obterInfoWebhook(Persona $persona, TelegramWebhookService $webhook): ?array
    {
        if ($persona->canal !== 'telegram' || empty($persona->telegram_token)) {
            return null;
        }

        $result = $webhook->info($persona->telegram_token);

        return $result['ok'] ? $result['info'] : null;
    }
}
