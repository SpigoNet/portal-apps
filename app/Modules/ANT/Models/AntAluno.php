<?php
namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntAluno extends Model
{
    protected $table = 'ant_alunos';
    protected $fillable = ['user_id', 'ra', 'nome'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // Configuração correta para N:N usando RA
    public function materias()
    {
        return $this->belongsToMany(
            AntMateria::class,
            'ant_aluno_materia',
            'aluno_ra', // FK na tabela pivô
            'materia_id', // FK na outra tabela
            'ra', // Chave local neste model (String RA)
            'id' // Chave local no outro model
        )->withPivot('semestre');
    }

    public function entregas()
    {
        return $this->hasMany(AntEntrega::class, 'aluno_ra', 'ra');
    }
}
