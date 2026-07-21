<?php

namespace App\Modules\TreeTask\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model; // Assumindo que o model User está aqui

class Projeto extends Model
{
    protected $table = 'treetask_projetos';

    protected $primaryKey = 'id_projeto';

    protected $fillable = [
        'nome',
        'descricao',
        'status',
        'id_user_owner',
        'data_inicio',
        'data_prevista_termino',
        'data_conclusao_real',
    ];

    protected $casts = [
        'id_user_owner' => 'integer',
    ];

    // Relacionamento: Projeto tem muitas fases
    public function fases()
    {
        return $this->hasMany(Fase::class, 'id_projeto', 'id_projeto');
    }

    // Dono do projeto
    public function owner()
    {
        return $this->belongsTo(User::class, 'id_user_owner', 'id');
    }
}
