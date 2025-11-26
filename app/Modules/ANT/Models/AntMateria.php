<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntMateria extends Model
{
    protected $table = 'ant_materias';
    protected $fillable = ['nome', 'nome_curto'];

    public function professores()
    {
        return $this->belongsToMany(\App\Models\User::class, 'ant_professor_materia', 'materia_id', 'user_id')
            ->withPivot('semestre');
    }

    public function alunos()
    {
        // Busca o semestre na configuração ou calcula fallback
        $semestreAtual = AntConfiguracao::value('semestre_atual')
            ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        return $this->belongsToMany(
            AntAluno::class,
            'ant_aluno_materia',
            'materia_id',
            'aluno_ra',
            'id',
            'ra'
        )->withPivot('semestre')
        ->wherePivot('semestre', $semestreAtual);
    }

    public function trabalhos()
    {
        return $this->hasMany(AntTrabalho::class, 'materia_id');
    }
}
