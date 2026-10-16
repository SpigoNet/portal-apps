<?php

namespace App\Modules\Cardapio\Services\Assistente;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Casamento tolerante de nomes ditos em linguagem natural com os cadastros da casa.
 */
class Nomes
{
    /**
     * Minúsculas, sem acento e pontuação; plural simples (tomates → tomate).
     */
    public static function chave(string $texto): string
    {
        $texto = preg_replace('/[^a-z0-9 ]+/', ' ', strtolower(Str::ascii($texto))) ?? '';
        $palavras = array_filter(explode(' ', $texto), fn (string $p) => $p !== '');

        return implode(' ', array_map(fn (string $p) => strlen($p) > 3 && str_ends_with($p, 's') ? substr($p, 0, -1) : $p, $palavras));
    }

    /**
     * Encontra o item cujo nome casa com $nome: igualdade primeiro; depois contenção por palavras
     * (“peito de frango” ↔ “Frango”), desde que só um item case.
     *
     * @template T
     *
     * @param  Collection<int, T>  $itens
     * @param  callable(T): string  $nomeDe
     * @return array{item: ?T, ambiguos: array<int, string>}
     */
    public static function achar(Collection $itens, string $nome, callable $nomeDe): array
    {
        $alvo = self::chave($nome);

        if ($alvo === '') {
            return ['item' => null, 'ambiguos' => []];
        }

        foreach ($itens as $item) {
            if (self::chave($nomeDe($item)) === $alvo) {
                return ['item' => $item, 'ambiguos' => []];
            }
        }

        $candidatos = $itens->filter(function ($item) use ($alvo, $nomeDe) {
            $chave = self::chave($nomeDe($item));

            return $chave !== '' && (str_contains(" {$chave} ", " {$alvo} ") || str_contains(" {$alvo} ", " {$chave} "));
        })->values();

        return match (true) {
            $candidatos->count() === 1 => ['item' => $candidatos->first(), 'ambiguos' => []],
            $candidatos->count() > 1 => ['item' => null, 'ambiguos' => $candidatos->map($nomeDe)->sort()->values()->all()],
            default => ['item' => null, 'ambiguos' => []],
        };
    }
}
