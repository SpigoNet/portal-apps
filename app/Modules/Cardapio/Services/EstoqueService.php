<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Exceptions\EstoqueInsuficienteException;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Ingrediente;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Regras de estoque: quantidades na unidade base do ingrediente, sem estoque negativo (RN11).
 */
class EstoqueService
{
    /**
     * Soma o estoque de um ingrediente (unidade base).
     */
    public function total(Casa $casa, Ingrediente $ingrediente): float
    {
        return (float) $casa->itensEstoque()->where('ingrediente_id', $ingrediente->id)->sum('quantidade');
    }

    /**
     * Adiciona ao estoque. Soma ao lote existente se for o mesmo ingrediente, local, validade e marca.
     *
     * @param  array{local?: string, validade?: ?string, marca?: ?string, lote?: ?string, aproximada?: bool, observacoes?: ?string, quantidade_minima?: ?float, quantidade_ideal?: ?float}  $dados
     */
    public function adicionar(Casa $casa, Ingrediente $ingrediente, float $quantidade, string $unidade, array $dados = []): ItemEstoque
    {
        $base = $this->paraBase($ingrediente, $quantidade, $unidade);

        if ($base <= 0) {
            throw new InvalidArgumentException('A quantidade deve ser maior que zero.');
        }

        $local = $dados['local'] ?? 'despensa';

        return DB::transaction(function () use ($casa, $ingrediente, $base, $local, $dados): ItemEstoque {
            $existente = $casa->itensEstoque()
                ->where('ingrediente_id', $ingrediente->id)
                ->where('local', $local)
                ->when(
                    ($dados['validade'] ?? null) !== null,
                    fn ($q) => $q->whereDate('validade', $dados['validade']),
                    fn ($q) => $q->whereNull('validade')
                )
                ->when(
                    ($dados['marca'] ?? null) !== null,
                    fn ($q) => $q->where('marca', $dados['marca']),
                    fn ($q) => $q->whereNull('marca')
                )
                ->first();

            if ($existente !== null) {
                $existente->increment('quantidade', $base);

                return $existente->refresh();
            }

            return $casa->itensEstoque()->create($dados + [
                'ingrediente_id' => $ingrediente->id,
                'local' => $local,
                'quantidade' => $base,
                'data_entrada' => today(),
            ]);
        });
    }

    /**
     * Consome estoque pelos lotes que vencem primeiro. Lotes esvaziados são removidos.
     * Sem $limitar, falta de estoque lança exceção e nada é alterado; com $limitar, consome o que houver.
     *
     * @return float quantidade efetivamente consumida (unidade base)
     */
    public function baixar(Casa $casa, Ingrediente $ingrediente, float $quantidadeBase, bool $limitar = false): float
    {
        if ($quantidadeBase <= 0) {
            return 0.0;
        }

        return DB::transaction(function () use ($casa, $ingrediente, $quantidadeBase, $limitar): float {
            /** @var Collection<int, ItemEstoque> $lotes */
            $lotes = $casa->itensEstoque()
                ->where('ingrediente_id', $ingrediente->id)
                ->orderByRaw('validade IS NULL')
                ->orderBy('validade')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $disponivel = (float) $lotes->sum('quantidade');

            if ($quantidadeBase > $disponivel + 0.0005 && ! $limitar) {
                throw new EstoqueInsuficienteException("Estoque insuficiente de {$ingrediente->nome}.");
            }

            $restante = min($quantidadeBase, $disponivel);
            $consumido = $restante;

            foreach ($lotes as $lote) {
                if ($restante <= 0) {
                    break;
                }

                $retirar = min($lote->quantidade, $restante);
                $restante -= $retirar;

                if ($lote->quantidade - $retirar <= 0.0005) {
                    $lote->delete();
                } else {
                    $lote->update(['quantidade' => $lote->quantidade - $retirar]);
                }
            }

            return $consumido;
        });
    }

    /**
     * Zera o estoque de um ingrediente (acabou): remove todos os lotes.
     *
     * @return float quantidade removida (unidade base)
     */
    public function zerar(Casa $casa, Ingrediente $ingrediente): float
    {
        return DB::transaction(function () use ($casa, $ingrediente): float {
            $total = $this->total($casa, $ingrediente);
            $casa->itensEstoque()->where('ingrediente_id', $ingrediente->id)->delete();

            return $total;
        });
    }

    /**
     * Define a quantidade de um lote (unidade base). Zero remove o lote; negativo é recusado.
     */
    public function ajustar(ItemEstoque $item, float $quantidadeBase): void
    {
        if ($quantidadeBase < 0) {
            throw new EstoqueInsuficienteException('O estoque não pode ficar negativo.');
        }

        if ($quantidadeBase <= 0.0005) {
            $item->delete();

            return;
        }

        $item->update(['quantidade' => $quantidadeBase]);
    }

    /**
     * Ingredientes cujo total em estoque está abaixo do mínimo configurado em algum lote.
     *
     * @return \Illuminate\Support\Collection<int, array{ingrediente: Ingrediente, total: float, minimo: float}>
     */
    public function estoqueBaixo(Casa $casa): \Illuminate\Support\Collection
    {
        return $casa->itensEstoque()->with('ingrediente')->whereNotNull('quantidade_minima')->get()
            ->groupBy('ingrediente_id')
            ->map(function ($lotes) use ($casa): array {
                $ingrediente = $lotes->first()->ingrediente;

                return [
                    'ingrediente' => $ingrediente,
                    'total' => $this->total($casa, $ingrediente),
                    'minimo' => (float) $lotes->max('quantidade_minima'),
                ];
            })
            ->filter(fn (array $linha): bool => $linha['total'] < $linha['minimo'])
            ->values();
    }

    /**
     * Lotes vencidos ou prestes a vencer, os mais urgentes primeiro.
     *
     * @return Collection<int, ItemEstoque>
     */
    public function vencendo(Casa $casa): Collection
    {
        return $casa->itensEstoque()->with('ingrediente')
            ->whereNotNull('validade')
            ->whereDate('validade', '<=', today()->addDays(ItemEstoque::DIAS_ATENCAO))
            ->orderBy('validade')
            ->get();
    }

    private function paraBase(Ingrediente $ingrediente, float $quantidade, string $unidade): float
    {
        if (! UnidadeConversor::compativel($unidade, $ingrediente->unidade_base)) {
            throw new InvalidArgumentException("A unidade {$unidade} não combina com {$ingrediente->nome} ({$ingrediente->unidade_base}).");
        }

        return UnidadeConversor::paraBase($quantidade, $unidade);
    }
}
