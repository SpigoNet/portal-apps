<?php

namespace App\Modules\MundosDeMim\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiSetting extends Model
{
    protected $table = 'mundos_de_mim_user_ai_settings';

    protected $fillable = [
        'user_id',
        'ai_provider_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'ai_provider_id');
    }
}
