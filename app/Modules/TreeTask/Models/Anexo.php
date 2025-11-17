<?php

namespace App\Modules\TreeTask\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Anexo extends Model
{
    protected $table = 'treetask_anexos';
    protected $primaryKey = 'id_anexo';

    protected $fillable = [
        'id_user_upload',
        'nome_arquivo',
        'path_arquivo',
        'mime_type',
        'tamanho'
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'id_user_upload', 'id');
    }

    public function tarefas()
    {
        return $this->belongsToMany(
            Tarefa::class,
            'treetask_anexo_tarefa',
            'id_anexo',
            'id_tarefa'
        );
    }
}
