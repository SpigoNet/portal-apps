<?php

namespace App\Modules\VocabularioControlado\Models;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    protected $connection = 'vocabulario_legacy';

    protected $table = 'perfil';

    // Tabela não tem coluna id numérica; mail é a chave natural
    protected $primaryKey = 'mail';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['nome', 'mail', 'perfil'];
}
