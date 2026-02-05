<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BolaoMeeting extends Model
{
    protected $fillable = ['name', 'status', 'finished_at', 'user_id'];

    protected $casts = [
        'finished_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guesses(): HasMany
    {
        return $this->hasMany(BolaoGuess::class, 'meeting_id');
    }
}
