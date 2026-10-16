<?php

namespace App\Modules\Pidgey\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liga um chat do Telegram a um usuário do portal. Só chats vinculados falam com o bot.
 */
class TelegramVinculo extends Model
{
    protected $table = 'pidgey_telegram_vinculos';

    protected $fillable = ['user_id', 'chat_id', 'username'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
