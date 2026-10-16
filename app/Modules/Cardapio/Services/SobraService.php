<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\ItemEstoque;
use App\Modules\Cardapio\Models\Receita;
use App\Modules\Cardapio\Models\RefeicaoPlanejada;
use App\Modules\Cardapio\Models\Sobra;
use App\Modules\Cardapio\Support\UnidadeConversor;
use Illuminate\Support\Facades\DB;

/**
 * Sobras são estoque de validade curta: cada tipo de sobra é um ingrediente marcado como `sobra`
 * ("Sobra de Frango assado"), que receitas podem usar e que ganha prioridade por vencer logo.
 */
class SobraService
{
    public function __construct(private readonly EstoqueService $estoque) {}

    /**
     * @return array{0: Sobra, 1: ItemEstoque}
     */
    public function registrar(
        Casa $casa,
        string $nome,
        float $quantidade,
        string $unidade,
        int $diasDeValidade,
        ?Receita $receita = null,
        ?RefeicaoPlanejada $refeicao = null,
        string $local = 'geladeira',
    ): array {
        $base = UnidadeConversor::baseDe($unidade);
        $nomeIngrediente = 'Sobra de '.$nome;

        return DB::transaction(function () use ($casa, $nomeIngrediente, $quantidade, $unidade, $base, $diasDeValidade, $receita, $refeicao, $local): array {
            $ingrediente = $casa->ingredientes()->where('nome', $nomeIngrediente)->first();

            // Mesma sobra em outra unidade base (ex.: g vs ml): vira um ingrediente separado.
            if ($ingrediente !== null && $ingrediente->unidade_base !== $base) {
                $nomeIngrediente .= " ({$base})";
                $ingrediente = $casa->ingredientes()->where('nome', $nomeIngrediente)->first();
            }

            $ingrediente ??= $casa->ingredientes()->create(['nome' => $nomeIngrediente, 'unidade_base' => $base, 'sobra' => true]);
            $validade = today()->addDays($diasDeValidade)->toDateString();

            $item = $this->estoque->adicionar($casa, $ingrediente, $quantidade, $unidade, ['local' => $local, 'validade' => $validade]);

            $sobra = $casa->sobras()->create([
                'ingrediente_id' => $ingrediente->id,
                'receita_id' => $receita?->id,
                'refeicao_planejada_id' => $refeicao?->id,
                'quantidade' => UnidadeConversor::paraBase($quantidade, $unidade),
                'validade' => $validade,
            ]);

            return [$sobra, $item];
        });
    }
}
