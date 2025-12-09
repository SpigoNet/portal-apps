<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fatura extends Model
{
    use HasFactory;

    protected $table = 'mithril_faturas';

    protected $fillable = [
        'user_id',
        'conta_id', // O cartão
        'mes',
        'ano',
        'valor_total',
        'data_pagamento',
        'conta_pagamento_id', // Conta usada para pagar
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor_total' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->user_id && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });

        static::addGlobalScope('user', function ($builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
            }
        });
    }

    // O cartão de crédito a que esta fatura pertence
    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    // A conta bancária usada para pagar esta fatura
    public function contaPagamento()
    {
        return $this->belongsTo(Conta::class, 'conta_pagamento_id');
    }

    public function itens()
    {
        return $this->hasMany(CartaoFaturaItem::class, 'fatura_id');
    }
}
