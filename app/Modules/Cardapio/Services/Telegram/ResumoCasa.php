<?php

namespace App\Modules\Cardapio\Services\Telegram;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemCompra;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Services\EstoqueService;
use App\Modules\Cardapio\Services\PlanejadorService;
use App\Modules\Cardapio\Support\TipoRefeicao;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Support\Carbon;

/**
 * Textos (HTML do Telegram) com os dados da casa. Determinístico: nada aqui passa pela IA.
 */
class ResumoCasa
{
    private const LIMITE_LINHAS = 40;

    public function __construct(private readonly EstoqueService $estoque) {}

    /**
     * @return array<int, string> linhas; vazio quando não há nada planejado
     */
    public function refeicoes(Casa $casa, Carbon $dia): array
    {
        $plano = $casa->planos()->whereDate('inicio_semana', PlanejadorService::segundaDaSemana($dia))->first();

        if ($plano === null) {
            return [];
        }

        $ordem = array_keys(TipoRefeicao::rotulos());
        $rotulos = TipoRefeicao::rotulos();

        return $plano->refeicoes()->whereDate('data', $dia)->with(['receita', 'pessoas'])->get()
            ->sortBy(fn ($r) => array_search($r->tipo_refeicao, $ordem))
            ->map(fn ($r): string => '• <b>'.e($rotulos[$r->tipo_refeicao] ?? $r->tipo_refeicao).':</b> '
                .e($r->receita?->nome ?? 'sem receita definida')
                .($r->consumida() ? ' ✔' : ''))
            ->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public function vencendo(Casa $casa): array
    {
        return $this->estoque->vencendo($casa)->take(self::LIMITE_LINHAS)->map(function (ItemEstoque $i): string {
            $dias = $i->diasParaVencer();
            $quando = $dias < 0 ? 'venceu há '.abs($dias).' dia(s)' : ($dias === 0 ? 'vence hoje' : "vence em {$dias} dia(s)");

            return '• '.e($i->ingrediente->nome).' — '.$quando;
        })->all();
    }

    /**
     * @return array<int, string>
     */
    public function estoqueBaixo(Casa $casa): array
    {
        return $this->estoque->estoqueBaixo($casa)->take(self::LIMITE_LINHAS)->map(function (array $l): string {
            $base = $l['ingrediente']->unidade_base;

            return '• '.e($l['ingrediente']->nome).' — '.UnidadeConversor::formatar($l['total'], $base).' (mínimo '.UnidadeConversor::formatar($l['minimo'], $base).')';
        })->all();
    }

    /**
     * Itens ainda por comprar das listas abertas.
     *
     * @return array<int, string>
     */
    public function compras(Casa $casa): array
    {
        $itens = ItemCompra::with('ingrediente')
            ->whereIn('lista_id', $casa->listasCompras()->where('status', 'aberta')->pluck('id'))
            ->where('comprado', false)->get();

        $linhas = $itens->take(self::LIMITE_LINHAS)->map(function (ItemCompra $i): string {
            $qtd = $i->ingrediente ? ' — '.UnidadeConversor::formatar($i->quantidade_comprar, $i->ingrediente->unidade_base) : '';

            return '• '.e($i->nome()).$qtd;
        })->all();

        if ($itens->count() > self::LIMITE_LINHAS) {
            $linhas[] = '… e mais '.($itens->count() - self::LIMITE_LINHAS).' item(ns).';
        }

        return $linhas;
    }

    /**
     * Lotes do alimento procurado, ou [] se nada combina.
     *
     * @return array<int, string>
     */
    public function procurarEstoque(Casa $casa, string $busca): array
    {
        $lotes = $casa->itensEstoque()->with('ingrediente')
            ->whereHas('ingrediente', fn ($q) => $q->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%']))
            ->orderBy('validade')->get()->groupBy('ingrediente_id');

        return $lotes->take(15)->map(function ($grupo): string {
            $ing = $grupo->first()->ingrediente;
            $detalhe = $grupo->map(fn (ItemEstoque $l): string => UnidadeConversor::formatar($l->quantidade, $ing->unidade_base)
                .' ('.mb_strtolower(ItemEstoque::LOCAIS[$l->local]).($l->validade ? ', vence '.$l->validade->format('d/m') : '').')')->implode('; ');

            return '• <b>'.e($ing->nome).':</b> '.$detalhe;
        })->values()->all();
    }
}
