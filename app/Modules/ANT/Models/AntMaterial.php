<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntMaterial extends Model
{
    protected $table = 'ant_materiais';

    protected $fillable = [
        'materia_id',
        'user_id',
        'semestre',
        'data_aula',
        'titulo',
        'descricao',
        'arquivos',
    ];

    protected $casts = [
        'data_aula' => 'date',
    ];

    public function materia()
    {
        return $this->belongsTo(AntMateria::class, 'materia_id');
    }

    public function professor()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
