<?php

namespace App\Modules\Pidgey\Contracts;

use App\Models\User;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Support\RespostaTelegram;

/**
 * O módulo dono de um bot do Telegram. Cada módulo tem o seu bot e a sua persona (Alfred\Persona: token,
 * slug e personalidade; ex.: Cardápio = Sanji). O Pidgey cuida do canal (webhook, vínculo, envio);
 * o módulo decide o que os comandos, textos e botões significam.
 */
interface ManipuladorTelegram
{
    /**
     * Slug da persona (Alfred) cujo bot este módulo atende.
     */
    public function persona(): string;

    /**
     * Texto de ajuda do bot (exibido em /ajuda e após o vínculo).
     */
    public function ajuda(): string;

    /**
     * Trata uma mensagem de um usuário já vinculado. Devolve null quando não é assunto do módulo.
     */
    public function mensagem(Persona $persona, User $usuario, string $texto): ?RespostaTelegram;

    /**
     * Trata o toque num botão (callback_data). Devolve null quando o botão não é do módulo.
     */
    public function botao(Persona $persona, User $usuario, string $dados): ?RespostaTelegram;
}
