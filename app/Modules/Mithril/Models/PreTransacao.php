<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreTransacao extends Model
{
    use HasFactory;

    protected $table = 'mithril_pre_transacoes';

    protected $fillable = [
        'user_id',
        'descricao',
        'valor_parcela',
        'conta_id',
        'dia_vencimento',
        'tipo', // 'parcelada' ou 'recorrente'
        'total_parcelas',
        'parcela_atual',
        'data_inicio',
        'ativa',
        'data_ultima_acao',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_ultima_acao' => 'date',
        'valor_parcela' => 'decimal:2',
        'ativa' => 'boolean',
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

    public function transacoes()
    {
        return $this->hasMany(Transacao::class, 'pre_transacao_id');
    }
}
