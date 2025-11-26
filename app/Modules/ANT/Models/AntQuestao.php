<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntQuestao extends Model
{
    protected $table = 'ant_questoes';

    protected $fillable = [
        'enunciado', 'database_name', 'query_correta',
        'dissertativa', 'multipla_escolha'
    ];

    protected $casts = [
        'dissertativa' => 'boolean',
        'multipla_escolha' => 'boolean',
    ];

    public function alternativas()
    {
        return $this->hasMany(AntAlternativa::class, 'questao_id');
    }

    public function provas()
    {
        return $this->belongsToMany(AntProva::class, 'ant_prova_questoes', 'questao_id', 'prova_id')
            ->withPivot('ordem');
    }
}
