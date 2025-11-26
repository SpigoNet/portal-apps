<?php

namespace App\Modules\TreeTask\Models;

use Illuminate\Database\Eloquent\Model;

class GamificationReward extends Model
{
    protected $table = 'treetask_gamification_rewards';
    protected $primaryKey = 'id_reward';

    protected $fillable = [
        'nome',
        'tipo',
        'chance',
        'moeda_ganha',
        'descricao'
    ];
}
