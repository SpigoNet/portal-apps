<?php

namespace App\Modules\Pidgey\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Um chat do Telegram acabou de ser vinculado a um usuário do portal.
 */
class TelegramVinculado
{
    use Dispatchable;

    public function __construct(public readonly int $userId) {}
}
