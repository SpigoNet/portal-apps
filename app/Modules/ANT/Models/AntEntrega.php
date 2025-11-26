<?php
namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntEntrega extends Model
{
    protected $table = 'ant_entregas';

    protected $fillable = [
        'trabalho_id', 'aluno_ra', 'arquivos',
        'comentario_aluno', 'data_entrega', 'nota', 'comentario_professor'
    ];

    protected $casts = [
        'data_entrega' => 'datetime',
    ];

    public function trabalho()
    {
        return $this->belongsTo(AntTrabalho::class, 'trabalho_id');
    }

    public function aluno()
    {
        return $this->belongsTo(AntAluno::class, 'aluno_ra', 'ra');
    }
}
