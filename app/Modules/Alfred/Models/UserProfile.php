<?php

namespace App\Modules\Alfred\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $table = 'alfred_user_profiles';

    protected $fillable = [
        'user_id',
        'treetask_url',
        'treetask_user_id',
        'treetask_token',
        'meta_agua_ml',
        'energia_atual',
        'modo_dia_ruim',
        'dia_ruim_ativado_em',
    ];

    protected $casts = [
        'meta_agua_ml' => 'integer',
        'modo_dia_ruim' => 'boolean',
        'dia_ruim_ativado_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
