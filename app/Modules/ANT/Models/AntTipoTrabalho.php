<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntTipoTrabalho extends Model
{
    protected $table = 'ant_tipos_trabalho';
    protected $fillable = ['descricao', 'arquivos'];

    public function trabalhos()
    {
        return $this->hasMany(AntTrabalho::class, 'tipo_trabalho_id');
    }
}
