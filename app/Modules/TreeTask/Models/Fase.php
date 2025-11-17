<?php

namespace App\Modules\TreeTask\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\TreeTask\Models\Anexo;

class Fase extends Model
{
    protected $table = 'treetask_fases';
    protected $primaryKey = 'id_fase';

    protected $fillable = [
        'id_projeto', 'nome', 'descricao', 'status', 'ordem'
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }

    public function tarefas()
    {
        return $this->hasMany(Tarefa::class, 'id_fase', 'id_fase');
    }


}
