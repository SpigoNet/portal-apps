<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Importante
use App\Models\User;

class WhatsAppService
{
    protected string $baseUrl = 'https://api.callmebot.com/whatsapp.php';
    protected string $adminEmail = 'spiriguidiberto@gmail.com';

    /**
     * Envia para o Admin (busca pelo email fixo).
     */
    public function sendToAdmin(string $message): bool
    {
        $admin = User::where('email', $this->adminEmail)->first();

        if (!$admin) {
            Log::error("WhatsApp: Admin não encontrado.");
            return false;
        }

        return $this->sendToUser($admin, "[ADMIN] " . $message);
    }

    /**
     * NOVO: Envia para o usuário logado atual.
     * Centraliza a lógica de Auth aqui.
     */
    public function sendToMe(string $message): bool
    {
        if (!Auth::check()) {
            Log::warning('WhatsApp: Tentativa de uso de sendToMe sem usuário logado.');
            return false;
        }

        return $this->sendToUser(Auth::user(), $message);
    }

    /**
     * Envia para um Usuário específico.
     */
    public function sendToUser(User $user, string $message): bool
    {
        if (empty($user->whatsapp_phone) || empty($user->whatsapp_apikey)) {
            if ($user->email !== $this->adminEmail) {
                Log::warning("WhatsApp: Usuário {$user->id} sem credenciais.");
            }
            return false;
        }

        return $this->sendMessage($user->whatsapp_phone, $user->whatsapp_apikey, $message);
    }

    /**
     * Método base de disparo HTTP
     */
    protected function sendMessage($phone, $apikey, $text): bool
    {
        try {
            $response = Http::get($this->baseUrl, [
                'phone' => $phone,
                'text' => $text,
                'apikey' => $apikey,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp Exception: ' . $e->getMessage());
            return false;
        }
    }
}
