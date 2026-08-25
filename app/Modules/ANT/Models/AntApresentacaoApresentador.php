<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntApresentacaoApresentador extends Model
{
    protected $table = 'ant_apresentacao_apresentadores';

    protected $fillable = ['agendamento_id', 'aluno_ra', 'nota', 'comentario'];

    public function agendamento()
    {
        return $this->belongsTo(AntApresentacaoAgendamento::class, 'agendamento_id');
    }

    public function aluno()
    {
        return $this->belongsTo(AntAluno::class, 'aluno_ra', 'ra');
    }

    public function entrega()
    {
        return $this->hasOne(AntApresentacaoEntrega::class, 'apresentador_id');
    }
}
