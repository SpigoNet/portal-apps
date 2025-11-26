<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntConfiguracao extends Model
{
    protected $table = 'ant_configuracoes';

    protected $fillable = ['semestre_atual', 'admins', 'prompt_agente'];

    /**
     * Verifica se um dado e-mail está na lista de admins.
     */
    public function isAdmin(string $email): bool
    {
        if (empty($this->admins)) {
            return false;
        }

        // Separa por vírgula, remove espaços e verifica
        $listaAdmins = array_map('trim', explode(',', $this->admins));
        return in_array($email, $listaAdmins);
    }
}
