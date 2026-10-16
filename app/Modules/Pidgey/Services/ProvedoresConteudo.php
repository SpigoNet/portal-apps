<?php

namespace App\Modules\Pidgey\Services;

use App\Modules\Pidgey\Contracts\ProvedorConteudo;

/**
 * Registro dos módulos que fornecem conteúdo dinâmico completo para os agendamentos (singleton).
 */
class ProvedoresConteudo
{
    /** @var array<string, ProvedorConteudo> */
    private array $provedores = [];

    public function registrar(ProvedorConteudo $provedor): void
    {
        $this->provedores[$provedor->tipo()] = $provedor;
    }

    public function para(string $tipo): ?ProvedorConteudo
    {
        return $this->provedores[$tipo] ?? null;
    }
}
