<?php

namespace App\Modules\StreamingManager\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingPayment extends Model
{
    protected $fillable = [
        'streaming_id',
        'user_id',
        'amount',
        'status',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function streaming(): BelongsTo
    {
        return $this->belongsTo(Streaming::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPayerNameAttribute()
    {
        return $this->user ? $this->user->name : ($this->note ?? 'Desconhecido');
    }
}
