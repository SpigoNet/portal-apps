<?php

namespace App\Modules\Mithril\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegraDescricao extends Model
{
    use HasFactory;

    protected $table = 'mithril_regras_descricao';

    protected $fillable = [
        'user_id',
        'descricao_original',
        'descricao_detalhada',
        'classificacao_id',
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

    public function classificacao()
    {
        return $this->belongsTo(Classificacao::class, 'classificacao_id');
    }
}
