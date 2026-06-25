<?php

namespace App\Modules\Alfred\Models;

use Illuminate\Database\Eloquent\Model;

class RotinaExecucao extends Model
{
    protected $table = 'alfred_rotina_execucoes';

    protected $fillable = [
        'rotina_id',
        'data_execucao',
        'hora_execucao',
        'observacao',
    ];

    protected $casts = [
        'data_execucao' => 'date',
        'hora_execucao' => 'datetime',
    ];

    public function rotina()
    {
        return $this->belongsTo(Rotina::class, 'rotina_id');
    }
}
