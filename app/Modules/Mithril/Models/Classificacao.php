<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classificacao extends Model
{
    use HasFactory;

    protected $table = 'mithril_classificacoes';

    protected $fillable = [
        'user_id',
        'nome',
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

    public function itens()
    {
        return $this->hasMany(CartaoFaturaItem::class, 'classificacao_id');
    }

    public function regras()
    {
        return $this->hasMany(RegraDescricao::class, 'classificacao_id');
    }
}
