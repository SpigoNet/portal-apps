<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntApresentacaoEntrega extends Model
{
    protected $table = 'ant_apresentacao_entregas';

    protected $fillable = ['apresentador_id', 'arquivos', 'comentario_aluno', 'data_entrega'];

    protected $casts = [
        'data_entrega' => 'datetime',
    ];

    public function apresentador()
    {
        return $this->belongsTo(AntApresentacaoApresentador::class, 'apresentador_id');
    }
}
