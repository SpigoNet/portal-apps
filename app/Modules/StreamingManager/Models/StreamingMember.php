<?php

namespace App\Modules\StreamingManager\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingMember extends Model
{
    protected $fillable = [
        'streaming_id',
        'user_id',
        'email',
    ];

    public function streaming(): BelongsTo
    {
        return $this->belongsTo(Streaming::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
