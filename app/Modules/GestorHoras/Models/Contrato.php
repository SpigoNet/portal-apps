<?php

namespace App\Modules\GestorHoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    protected $table = 'gh_contratos';

    protected $fillable = [
        'gh_cliente_id',
        'titulo',
        'tipo', // 'fixo' ou 'recorrente'
        'horas_contratadas',
        'data_inicio',
        'data_fim',
        'status',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'horas_contratadas' => 'decimal:2',
    ];

    /**
     * Contrato pertence a um Cliente (Empresa).
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'gh_cliente_id');
    }

    /**
     * Contrato tem vários apontamentos.
     */
    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'gh_contrato_id');
    }

    /**
     * Calcula horas consumidas.
     * Se recorrente: soma apenas mês atual.
     * Se fixo: soma tudo.
     */
    public function getHorasConsumidasAttribute()
    {
        // A soma total é simplesmente a soma de todos os apontamentos vinculados a este contrato
        $minutos = $this->apontamentos->sum('minutos_gastos');
        return $minutos > 0 ? round($minutos / 60, 2) : 0;
    }

    /**
     * Calcula saldo restante.
     */
    public function getSaldoAttribute()
    {
        // Total Contratado (Soma das horas estimadas de TODOS os itens/meses)
        // Isso é mais preciso do que usar o campo fixo do contrato
        $totalContratado = $this->itens->sum('horas_estimadas');

        // Se não tiver itens (caso raro), usa o campo do contrato
        if ($totalContratado == 0) {
            $totalContratado = $this->horas_contratadas;
        }

        return $totalContratado - $this->horas_consumidas;
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ContratoItem::class, 'gh_contrato_id');
    }
}
