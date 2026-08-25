<?php

namespace App\Modules\Pidgey\Models;

use Illuminate\Database\Eloquent\Model;

class ConteudoDinamico extends Model
{
    protected $table = 'pidgey_conteudos_dinamicos';

    protected $fillable = [
        'nome',
        'tipo',
        'conteudo',
        'ativo',
        'sistema',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'sistema' => 'boolean',
    ];

    public function agendamentos()
    {
        return $this->belongsToMany(
            Agendamento::class,
            'agendamento_conteudo_dinamico',
            'conteudo_dinamico_id',
            'agendamento_id'
        );
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'relatorio_financeiro' => 'Relatório Financeiro',
            'texto' => 'Texto pré-pronto',
            default => (string) $this->tipo,
        };
    }
}
