<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ListaCompras;
use App\Modules\Cardapio\Models\PlanoSemanal;

/**
 * Lista de compras = déficit entre o que o cardápio precisa e o que existe em estoque (RN10).
 */
class ComprasService
{
    public function __construct(private readonly EstoqueService $estoque) {}

    /**
     * Gera (ou recalcula) a lista do plano. Itens manuais e já comprados são preservados; o que já foi
     * comprado mas ainda não entrou no estoque conta como disponível para não duplicar a compra.
     * Produtos recorrentes ativos entram na mesma conta.
     */
    public function gerarParaPlano(PlanoSemanal $plano): ListaCompras
    {
        $casa = $plano->casa;
        $lista = $plano->listaCompras()->where('status', 'aberta')->first()
            ?? $casa->listasCompras()->create([
                'plano_id' => $plano->id,
                'titulo' => 'Semana de '.$plano->inicio_semana->format('d/m'),
            ]);

        $necessario = [];
        $refeicoes = $plano->refeicoes()->whereNull('consumida_em')->whereNotNull('receita_id')
            ->with(['receita.ingredientes.ingrediente', 'itens.receita.ingredientes'])->get();

        foreach ($refeicoes as $refeicao) {
            foreach ($refeicao->necessidades() as $ingredienteId => $quantidade) {
                $necessario[$ingredienteId] = ($necessario[$ingredienteId] ?? 0) + $quantidade;
            }
        }

        return $this->preencher($lista, $necessario);
    }

    /**
     * Lista só com os produtos recorrentes (compra de rotina, sem cardápio).
     */
    public function gerarRecorrentes(Casa $casa): ListaCompras
    {
        $lista = $casa->listasCompras()->where('status', 'aberta')->whereNull('plano_id')->where('titulo', 'Compras recorrentes')->first()
            ?? $casa->listasCompras()->create(['titulo' => 'Compras recorrentes']);

        return $this->preencher($lista, []);
    }

    /**
     * @param  array<int, float>  $necessario  ingrediente_id => quantidade necessária pelo cardápio
     */
    private function preencher(ListaCompras $lista, array $necessario): ListaCompras
    {
        $casa = $lista->casa;
        $recorrentes = $casa->produtosRecorrentes()->with('ingrediente')->where('ativo', true)->get();

        foreach ($recorrentes->whereNotNull('ingrediente_id')->where('quantidade', '>', 0) as $produto) {
            $necessario[$produto->ingrediente_id] = ($necessario[$produto->ingrediente_id] ?? 0) + $produto->quantidade;
        }

        $jaComprado = $lista->itens()->where('comprado', true)->where('adicionado_ao_estoque', false)
            ->whereNotNull('ingrediente_id')->get()->groupBy('ingrediente_id')
            ->map(fn ($itens) => (float) $itens->sum('quantidade_comprar'));

        $lista->itens()->where('manual', false)->where('comprado', false)->delete();

        $ingredientes = $casa->ingredientes()->whereIn('id', array_keys($necessario))->get()->keyBy('id');

        foreach ($necessario as $ingredienteId => $quantidade) {
            $emEstoque = $this->estoque->total($casa, $ingredientes[$ingredienteId]);
            $comprar = $quantidade - $emEstoque - ($jaComprado[$ingredienteId] ?? 0);

            if ($comprar <= 0.0005) {
                continue;
            }

            $lista->itens()->create([
                'ingrediente_id' => $ingredienteId,
                'quantidade_necessaria' => $quantidade,
                'quantidade_estoque' => $emEstoque,
                'quantidade_comprar' => $comprar,
            ]);
        }

        // Itens livres (papel higiênico, detergente…): entram uma vez por lista, mesmo depois de comprados.
        foreach ($recorrentes->whereNull('ingrediente_id') as $produto) {
            if (! $lista->itens()->where('descricao', $produto->descricao)->exists()) {
                $lista->itens()->create(['descricao' => $produto->descricao]);
            }
        }

        return $lista;
    }

    /**
     * Passa para o estoque os itens comprados que ainda não entraram.
     *
     * @return int quantidade de itens adicionados
     */
    public function adicionarCompradosAoEstoque(ListaCompras $lista, string $local = 'despensa'): int
    {
        $adicionados = 0;

        $itens = $lista->itens()->with('ingrediente')
            ->where('comprado', true)->where('adicionado_ao_estoque', false)
            ->whereNotNull('ingrediente_id')->where('quantidade_comprar', '>', 0)->get();

        foreach ($itens as $item) {
            $this->estoque->adicionar($lista->casa, $item->ingrediente, $item->quantidade_comprar, $item->ingrediente->unidade_base, ['local' => $local]);
            $item->update(['adicionado_ao_estoque' => true]);
            $adicionados++;
        }

        return $adicionados;
    }
}
