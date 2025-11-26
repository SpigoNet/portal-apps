<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntProva extends Model
{
    protected $table = 'ant_provas';
    protected $fillable = ['descricao', 'disponivel', 'trabalho_id'];

    protected $casts = [
        'disponivel' => 'boolean',
    ];

    // Se a prova valer nota como trabalho
    public function trabalho()
    {
        return $this->belongsTo(AntTrabalho::class, 'trabalho_id');
    }

    // Questões que compõem esta prova
    public function questoes()
    {
        return $this->belongsToMany(AntQuestao::class, 'ant_prova_questoes', 'prova_id', 'questao_id')
            ->withPivot('ordem')
            ->orderByPivot('ordem');
    }

    // Respostas dadas pelos alunos
    public function respostas()
    {
        return $this->hasMany(AntProvaResposta::class, 'prova_id');
    }
}
