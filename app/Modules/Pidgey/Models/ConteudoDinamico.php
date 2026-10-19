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
            'resumo_cardapio' => 'Resumo do Cardápio',
            'resumo_tarefas' => 'Tarefas do dia (TreeTask)',
            'fechamento_tarefas' => 'Fechamento das tarefas (TreeTask)',
            'contexto_dia' => 'Motivação com contexto do dia',
            'texto' => 'Texto pré-pronto',
            default => (string) $this->tipo,
        };
    }
}
