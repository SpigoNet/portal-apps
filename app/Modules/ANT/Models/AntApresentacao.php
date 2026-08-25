<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntApresentacao extends Model
{
    protected $table = 'ant_apresentacoes';

    protected $fillable = [
        'semestre', 'materia_id', 'nome', 'descricao',
        'peso_apresentacao_id', 'peso_participacao_id',
    ];

    public function materia()
    {
        return $this->belongsTo(AntMateria::class, 'materia_id');
    }

    public function pesoApresentacao()
    {
        return $this->belongsTo(AntPeso::class, 'peso_apresentacao_id');
    }

    public function pesoParticipacao()
    {
        return $this->belongsTo(AntPeso::class, 'peso_participacao_id');
    }

    public function agendamentos()
    {
        return $this->hasMany(AntApresentacaoAgendamento::class, 'apresentacao_id')
            ->orderBy('data');
    }

    /**
     * Total de estrelas distribuídas nesta atividade (3 por agendamento).
     */
    public function totalEstrelasPossiveis(): int
    {
        return $this->agendamentos()->count() * 3;
    }
}
