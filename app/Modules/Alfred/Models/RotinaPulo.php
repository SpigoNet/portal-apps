<?php

namespace App\Modules\Alfred\Models;

use Illuminate\Database\Eloquent\Model;

class RotinaPulo extends Model
{
    protected $table = 'alfred_rotina_pulos';

    protected $fillable = [
        'rotina_id',
        'data_pulo',
        'motivo',
    ];

    protected $casts = [
        'data_pulo' => 'date',
    ];

    public function rotina()
    {
        return $this->belongsTo(Rotina::class, 'rotina_id');
    }
}
