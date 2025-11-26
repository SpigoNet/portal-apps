<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntProvaResposta extends Model
{
    protected $table = 'ant_prova_respostas';

    protected $fillable = [
        'prova_id',
        'aluno_ra',    // <--- Corrigido: Usamos RA em vez de ID
        'questao_id',
        'resposta',
        'pre_avaliacao',
        'pontuacao',
        'quando'
    ];

    protected $casts = [
        'quando' => 'datetime',
    ];

    public function prova()
    {
        return $this->belongsTo(AntProva::class, 'prova_id');
    }

    // Configuração do relacionamento com Aluno pelo RA
    public function aluno()
    {
        return $this->belongsTo(AntAluno::class, 'aluno_ra', 'ra');
    }

    public function questao()
    {
        return $this->belongsTo(AntQuestao::class, 'questao_id');
    }
}
