<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transacao extends Model
{
    use HasFactory;

    protected $table = 'mithril_transacoes';

    protected $fillable = [
        'user_id',
        'descricao',
        'valor',
        'conta_id',
        'pre_transacao_id',
        'data_efetiva',
    ];

    protected $casts = [
        'data_efetiva' => 'date',
        'valor' => 'decimal:2'
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

    public function preTransacao()
    {
        return $this->belongsTo(PreTransacao::class, 'pre_transacao_id');
    }
}
