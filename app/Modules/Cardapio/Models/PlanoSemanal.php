<?php

namespace App\Modules\Cardapio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanoSemanal extends Model
{
    protected $table = 'cardapio_planos_semanais';

    protected $fillable = ['casa_id', 'inicio_semana', 'status'];

    protected function casts(): array
    {
        return ['inicio_semana' => 'date:Y-m-d'];
    }

    public function casa(): BelongsTo
    {
        return $this->belongsTo(Casa::class, 'casa_id');
    }

    public function refeicoes(): HasMany
    {
        return $this->hasMany(RefeicaoPlanejada::class, 'plano_id')->orderBy('data');
    }

    public function listaCompras(): HasMany
    {
        return $this->hasMany(ListaCompras::class, 'plano_id');
    }

    public function fimSemana(): \Illuminate\Support\Carbon
    {
        return $this->inicio_semana->copy()->addDays(6);
    }
}
