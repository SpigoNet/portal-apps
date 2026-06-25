<?php

namespace App\Modules\Alfred\Models;

use Illuminate\Database\Eloquent\Model;

class RotinaCategoria extends Model
{
    protected $table = 'alfred_rotina_categorias';

    protected $fillable = [
        'nome',
        'slug',
        'cor',
        'icone',
        'descricao',
        'ativa',
        'ordem',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function rotinas()
    {
        return $this->hasMany(Rotina::class, 'categoria', 'slug');
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }

    public function getBadgeAttribute()
    {
        return [
            'label' => $this->nome,
            'cor' => $this->cor,
            'icone' => $this->icone,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = \Illuminate\Support\Str::slug($categoria->nome);
            }
        });
    }
}
