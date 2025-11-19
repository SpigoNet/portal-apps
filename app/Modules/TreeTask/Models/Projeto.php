<?php

namespace App\Modules\TreeTask\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Assumindo que o model User está aqui
use App\Modules\TreeTask\Models\Anexo;

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
        'data_conclusao_real'
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
