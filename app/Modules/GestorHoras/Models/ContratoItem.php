<?php

namespace App\Modules\GestorHoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // <--- Importante

class ContratoItem extends Model
{
    protected $table = 'gh_contrato_itens';

    protected $fillable = [
        'gh_contrato_id',
        'titulo',
        'descricao',
        'horas_estimadas',
        'data_referencia', // <--- Novo
    ];

    protected $casts = [
        'horas_estimadas' => 'decimal:2',
        'data_referencia' => 'date', // <--- Novo
    ];

    // RELAÇÃO: Um item tem muitos apontamentos
    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'gh_contrato_item_id');
    }

    // CÁLCULO: Soma os minutos dos apontamentos vinculados
    public function getHorasRealizadasAttribute()
    {
        // Se a relação não foi carregada, retorna 0 para evitar erro
        if (!$this->relationLoaded('apontamentos')) {
            $this->load('apontamentos');
        }

        $minutos = $this->apontamentos->sum('minutos_gastos');
        return $minutos > 0 ? round($minutos / 60, 2) : 0;
    }
}
