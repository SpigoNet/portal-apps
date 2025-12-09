<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartaoFaturaItem extends Model
{
    use HasFactory;

    protected $table = 'mithril_cartao_fatura_itens';

    protected $fillable = [
        'user_id',
        'conta_id',
        'descricao',
        'descricao_detalhada',
        'classificacao_id',
        'valor',
        'data_compra',
        'fatura_id',
    ];

    protected $casts = [
        'data_compra' => 'date',
        'valor' => 'decimal:2',
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

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    public function classificacao()
    {
        return $this->belongsTo(Classificacao::class, 'classificacao_id');
    }

    public function fatura()
    {
        return $this->belongsTo(Fatura::class, 'fatura_id');
    }
}
