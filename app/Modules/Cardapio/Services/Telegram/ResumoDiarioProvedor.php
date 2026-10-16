<?php

namespace App\Modules\Cardapio\Services\Telegram;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Pidgey\Contracts\ProvedorConteudo;
use App\Modules\Pidgey\Models\Agendamento;

/**
 * Resumo do dia da cozinha como conteúdo dinâmico do Pidgey (tipo `resumo_cardapio`). Determinístico:
 * a persona só dá voz à saudação.
 */
class ResumoDiarioProvedor implements ProvedorConteudo
{
    public const TIPO = 'resumo_cardapio';

    private const SEM_RECEITA = 'sem receita definida';

    public function __construct(
        private readonly ResumoCasa $resumo,
        private readonly VozPersona $voz,
    ) {}

    public function tipo(): string
    {
        return self::TIPO;
    }

    public function mensagem(Agendamento $agendamento, Persona $persona): ?string
    {
        $pessoa = Pessoa::with('casa')->where('user_id', $agendamento->user_id)->where('ativo', true)->first();

        if ($pessoa === null) {
            return null;
        }

        $casa = $pessoa->casa;
        $refeicoes = $this->resumo->refeicoes($casa, today());
        $vencendo = array_slice($this->resumo->vencendo($casa), 0, 8);
        $compras = $this->resumo->compras($casa);
        $semCardapio = collect($refeicoes)->every(fn (string $l): bool => str_contains($l, self::SEM_RECEITA));

        $abertura = $this->voz->abrir($persona, $this->fatos($refeicoes, $vencendo, $compras, $semCardapio))
            ?? $this->voz->falar($persona, 'Bom dia! Aqui está o resumo da cozinha de hoje.');

        return $abertura."\n\n".$this->resumoCompleto($refeicoes, $vencendo, $compras);
    }

    /**
     * Fatos em texto puro para a persona narrar.
     *
     * @param  array<int, string>  $refeicoes
     * @param  array<int, string>  $vencendo
     * @param  array<int, string>  $compras
     */
    private function fatos(array $refeicoes, array $vencendo, array $compras, bool $semCardapio): string
    {
        $linhas = [$semCardapio
            ? 'Cardápio de hoje: NÃO DEFINIDO (nenhuma refeição de hoje tem receita escolhida).'
            : "Cardápio de hoje:\n".$this->simples($refeicoes)];

        $linhas[] = $vencendo === [] ? 'Alimentos vencendo: nenhum.' : "Alimentos vencendo:\n".$this->simples($vencendo);
        $linhas[] = $compras === [] ? 'Compras: nada pendente.' : 'Compras: '.count($compras).' item(ns) pendente(s) (o usuário vê com /compras).';

        return implode("\n\n", $linhas);
    }

    /**
     * Resumo determinístico (HTML do Telegram), sempre acrescentado abaixo da abertura da persona.
     *
     * @param  array<int, string>  $refeicoes
     * @param  array<int, string>  $vencendo
     * @param  array<int, string>  $compras
     */
    private function resumoCompleto(array $refeicoes, array $vencendo, array $compras): string
    {
        $blocos = ["🍽️ <b>Hoje</b>\n".($refeicoes === [] ? 'Cardápio não definido.' : implode("\n", $refeicoes))];

        if ($vencendo !== []) {
            $blocos[] = "⚠ <b>Vencendo</b>\n".implode("\n", $vencendo);
        }
        if ($compras !== []) {
            $blocos[] = '🛒 '.count($compras).' item(ns) para comprar — /compras';
        }

        return implode("\n\n", $blocos);
    }

    private function plano(string $linhaHtml): string
    {
        return trim(html_entity_decode(strip_tags($linhaHtml)), "• \t");
    }

    /**
     * @param  array<int, string>  $linhas
     */
    private function simples(array $linhas): string
    {
        return implode("\n", array_map(fn (string $l): string => '• '.$this->plano($l), $linhas));
    }
}
