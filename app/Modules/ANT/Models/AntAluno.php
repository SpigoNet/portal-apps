<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\ANT\Models\AntConfiguracao;

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
     * Isso facilita o uso em $aluno->materias no dia a dia.
     */
    public function materias()
    {
        // Busca o semestre na configuração ou calcula fallback
        $semestreAtual = AntConfiguracao::value('semestre_atual')
            ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        return $this->belongsToMany(
            AntMateria::class,
            'ant_aluno_materia',
            'aluno_ra', // FK na tabela pivô
            'materia_id', // FK na outra tabela
            'ra', // Chave local neste model
            'id' // Chave local no outro model
        )
            ->withPivot('semestre')
            ->wherePivot('semestre', $semestreAtual); // <--- O FILTRO MÁGICO
    }

    /**
     * Relacionamento Completo: Todas as matérias de TODOS os semestres.
     * Use $aluno->historico para ver o passado.
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
            ->orderByPivot('semestre', 'desc'); // Ordena do mais recente para o antigo
    }

    public function entregas()
    {
        return $this->hasMany(AntEntrega::class, 'aluno_ra', 'ra');
    }
}
