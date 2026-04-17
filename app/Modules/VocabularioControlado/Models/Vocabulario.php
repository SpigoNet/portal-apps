<?php

namespace App\Modules\VocabularioControlado\Models;

use Illuminate\Database\Eloquent\Model;

class Vocabulario extends Model
{
    protected $connection = 'vocabulario_legacy';

    protected $table = 'vocabulario';

    public $timestamps = false;

    protected $fillable = [
        'palavra',
        'resumo',
        'solicitadoPor',
        'autorizadoPor',
        'status',
        'sugestaoPara',
        'motivoReprova',
        'unidade',
        'funcao',
    ];

    protected $casts = [
        'dt_solicitado' => 'datetime',
        'dt_atualizacao' => 'datetime',
    ];

    public function sugestoes(): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($this->sugestaoPara)) {
            return collect();
        }

        $ids = array_filter(explode(',', $this->sugestaoPara));

        return static::whereIn('id', $ids)->get();
    }
}
