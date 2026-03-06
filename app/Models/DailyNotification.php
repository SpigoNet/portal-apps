<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyNotification extends Model
{
    protected $fillable = [
        'user_id',
        'generation_id',
        'image_url',
        'message_text',
        'channel',
        'sent',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\MundosDeMim\Models\DailyGeneration::class, 'generation_id');
    }

    public function markAsSent(string $messageText): void
    {
        $this->update([
            'sent' => true,
            'sent_at' => now(),
            'message_text' => $messageText,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'sent' => false,
            'error_message' => $errorMessage,
        ]);
    }
}
