<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntApresentacaoAgendamento extends Model
{
    protected $table = 'ant_apresentacao_agendamentos';

    protected $fillable = ['apresentacao_id', 'data', 'tema'];

    protected $casts = [
        'data' => 'date',
    ];

    public function apresentacao()
    {
        return $this->belongsTo(AntApresentacao::class, 'apresentacao_id');
    }

    public function apresentadores()
    {
        return $this->hasMany(AntApresentacaoApresentador::class, 'agendamento_id')
            ->orderBy('aluno_ra');
    }

    public function estrelas()
    {
        return $this->hasMany(AntEstrela::class, 'agendamento_id');
    }

    /**
     * Alunos que receberam estrela neste agendamento.
     */
    public function alunosEstrela()
    {
        return $this->belongsToMany(
            AntAluno::class,
            'ant_estrelas',
            'agendamento_id',
            'aluno_ra',
            'id',
            'ra'
        );
    }
}
