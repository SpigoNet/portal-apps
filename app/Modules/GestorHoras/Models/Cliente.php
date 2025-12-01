<?php

namespace App\Modules\GestorHoras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Support\Str;

class Cliente extends Model
{
    protected $table = 'gh_clientes';

    protected $fillable = ['nome', 'documento', 'email_financeiro'];
    protected $hidden = ['access_token']; // Ocultar em arrays por segurança
    /**
     * Um cliente (empresa) tem vários usuários (funcionários/sócios).
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'gh_cliente_id');
    }

    /**
     * Um cliente tem vários contratos.
     */
    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'gh_cliente_id');
    }
    public function getPublicLinkAttribute()
    {
        if (!$this->access_token) {
            $this->access_token = Str::random(32);
            $this->save();
        }

        // Retorna a URL completa
        return route('gestor-horas.publico', $this->access_token);
    }
}
