<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntAlternativa extends Model
{
    protected $table = 'ant_alternativas';
    protected $fillable = ['questao_id', 'texto', 'correta',
        'explicacao'];

    protected $casts = [
        'correta' => 'boolean',
    ];

    public function questao()
    {
        return $this->belongsTo(AntQuestao::class, 'questao_id');
    }
}
