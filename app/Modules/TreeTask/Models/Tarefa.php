<?php

namespace App\Modules\TreeTask\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model
{
    protected $table = 'treetask_tarefas';

    protected $primaryKey = 'id_tarefa';

    protected $fillable = [
        'id_fase',
        'titulo',
        'descricao',
        'status',
        'id_user_responsavel',
        'prioridade',
        'data_vencimento',
        'estimativa_tempo',
    ];

    protected function casts(): array
    {
        return [
            'data_vencimento' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'id_fase', 'id_fase');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'id_user_responsavel', 'id');
    }

    public function anexos()
    {
        return $this->belongsToMany(
            Anexo::class,
            'treetask_anexo_tarefa',
            'id_tarefa',
            'id_anexo'
        );
    }
}
