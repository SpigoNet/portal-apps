<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntTrabalho extends Model
{
    protected $table = 'ant_trabalhos';

    protected $fillable = [
        'semestre', 'nome', 'descricao', 'dicas_correcao',
        'materia_id', 'tipo_trabalho_id', 'prazo', 'maximo_alunos', 'peso_id'
    ];

    protected $casts = [
        'prazo' => 'date',
    ];

    public function materia()
    {
        return $this->belongsTo(AntMateria::class, 'materia_id');
    }

    public function tipoTrabalho()
    {
        return $this->belongsTo(AntTipoTrabalho::class, 'tipo_trabalho_id');
    }

    public function peso()
    {
        return $this->belongsTo(AntPeso::class, 'peso_id');
    }

    public function entregas()
    {
        return $this->hasMany(AntEntrega::class, 'trabalho_id');
    }

    // Se o trabalho for uma prova vinculada
    public function prova()
    {
        return $this->hasOne(AntProva::class, 'trabalho_id');
    }
}
