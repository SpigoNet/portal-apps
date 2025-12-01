<?php

namespace App\Modules\GestorHoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apontamento extends Model
{
    protected $table = 'gh_apontamentos';

    protected $fillable = [
        'gh_contrato_id',
        'gh_contrato_item_id',
        'descricao',
        'data_realizacao',
        'minutos_gastos',
    ];

    protected $casts = [
        'data_realizacao' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ContratoItem::class, 'gh_contrato_item_id');
    }
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'gh_contrato_id');
    }

    /**
     * Retorna o tempo em formato decimal de horas (ex: 1.5).
     */
    public function getHorasAttribute()
    {
        return round($this->minutos_gastos / 60, 2);
    }

    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'gh_contrato_item_id');
    }

// Acessor para saber quantas horas já foram gastas neste item
    public function getHorasRealizadasAttribute()
    {
        $minutos = $this->apontamentos->sum('minutos_gastos');
        return $minutos > 0 ? round($minutos / 60, 2) : 0;
    }

// Acessor para percentual de conclusão (baseado em horas)
    public function getProgressoAttribute()
    {
        if ($this->horas_estimadas <= 0) return 0;
        $percentual = ($this->horas_realizadas / $this->horas_estimadas) * 100;
        return min($percentual, 100); // Trava em 100% visualmente
    }
}
