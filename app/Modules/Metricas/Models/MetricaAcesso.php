<?php

namespace App\Modules\Metricas\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Importando o model de usuário global

class MetricaAcesso extends Model
{
    use HasFactory;

    protected $table = 'metricas_acessos';

    protected $fillable = [
        'user_id',
        'modulo_nome',
        'url_acessada',
        'metodo_http',
        'ip_origem',
    ];

    /**
     * Relacionamento com o Usuário.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
