<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntPeso extends Model
{
    protected $table = 'ant_pesos';
    protected $fillable = ['semestre', 'materia_id', 'grupo', 'valor'];

    public function materia()
    {
        return $this->belongsTo(AntMateria::class, 'materia_id');
    }
}
