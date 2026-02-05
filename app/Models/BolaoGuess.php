<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BolaoGuess extends Model
{
    protected $fillable = ['meeting_id', 'name', 'guess', 'diff_seconds'];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(BolaoMeeting::class, 'meeting_id');
    }
}
