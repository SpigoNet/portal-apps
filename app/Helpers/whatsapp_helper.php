<?php

use App\Services\WhatsAppService;
use App\Models\User;

if (!function_exists('send_whatsapp_admin')) {
    function send_whatsapp_admin(string $text)
    {
        return app(WhatsAppService::class)->sendToAdmin($text);
    }
}

if (!function_exists('send_whatsapp_user')) {
    function send_whatsapp_user(User $user, string $text)
    {
        return app(WhatsAppService::class)->sendToUser($user, $text);
    }
}

if (!function_exists('send_whatsapp_me')) {
    function send_whatsapp_me(string $text)
    {
        // Apenas repassa a chamada. Toda lógica está no Service.
        return app(WhatsAppService::class)->sendToMe($text);
    }
}
