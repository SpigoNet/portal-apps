<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conta extends Model
{
    use HasFactory;

    protected $table = 'mithril_contas';

    protected $fillable = [
        'user_id',
        'nome',
        'tipo',
        'saldo_inicial',
        'dia_fechamento',
        'dia_vencimento',
        'conta_debito_id',
    ];

    /**
     * Boot do Model para garantir que sempre salvamos o user_id.
     */
    protected static function booted()
    {
        static::creating(function ($conta) {
            if (!$conta->user_id && auth()->check()) {
                $conta->user_id = auth()->id();
            }
        });

        // Escopo Global para sempre filtrar pelo usuário logado
        static::addGlobalScope('user', function ($builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
            }
        });
    }

    // Relacionamentos
    public function transacoes()
    {
        return $this->hasMany(Transacao::class, 'conta_id');
    }

    public function saldosFechamento()
    {
        return $this->hasMany(SaldoFechamento::class, 'conta_id');
    }
}
