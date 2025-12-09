<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaldoFechamento extends Model
{
    use HasFactory;

    protected $table = 'mithril_saldos_fechamento';

    protected $fillable = [
        'user_id',
        'conta_id',
        'ano',
        'mes',
        'saldo_final',
        'data_fechamento',
    ];

    protected $casts = [
        'saldo_final' => 'decimal:2',
        'data_fechamento' => 'datetime',
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
}
