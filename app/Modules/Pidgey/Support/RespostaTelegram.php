<?php

namespace App\Modules\Pidgey\Support;

/**
 * Resposta do bot: texto em HTML do Telegram e, opcionalmente, botões em linhas ([rótulo, callback_data]).
 */
final class RespostaTelegram
{
    /**
     * @param  array<int, array<int, array{0: string, 1: string}>>  $botoes
     */
    public function __construct(public readonly string $texto, public readonly array $botoes = []) {}

    /**
     * @return array<string, mixed>|null
     */
    public function teclado(): ?array
    {
        if ($this->botoes === []) {
            return null;
        }

        return ['inline_keyboard' => array_map(
            fn (array $linha): array => array_map(fn (array $b): array => ['text' => $b[0], 'callback_data' => $b[1]], $linha),
            $this->botoes
        )];
    }
}
