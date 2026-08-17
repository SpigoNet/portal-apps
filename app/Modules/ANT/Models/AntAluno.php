<?php

namespace App\Modules\ANT\Models;

use App\Modules\ANT\Services\SemestreService;
use Illuminate\Database\Eloquent\Model;

class AntAluno extends Model
{
    protected $table = 'ant_alunos';

    protected $fillable = ['user_id', 'ra', 'nome'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Relacionamento Padrão: Apenas matérias do SEMESTRE ATUAL.
     */
    public function materias()
    {
        $semestreAtual = SemestreService::getCurrent();

        return $this->belongsToMany(
            AntMateria::class,
            'ant_aluno_materia',
            'aluno_ra',
            'materia_id',
            'ra',
            'id'
        )
            ->withPivot('semestre')
            ->wherePivot('semestre', $semestreAtual);
    }

    /**
     * Relacionamento Completo: Todas as matérias de TODOS os semestres.
     */
    public function historico()
    {
        return $this->belongsToMany(
            AntMateria::class,
            'ant_aluno_materia',
            'aluno_ra',
            'materia_id',
            'ra',
            'id'
        )
            ->withPivot('semestre')
            ->orderByPivot('semestre', 'desc');
    }

    public function entregas()
    {
        return $this->hasMany(AntEntrega::class, 'aluno_ra', 'ra');
    }
}
