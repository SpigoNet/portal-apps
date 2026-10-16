<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Models\Ingrediente;
use App\Modules\Cardapio\Models\RefeicaoPlanejada;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Estado da semana usado pelo motor: estoque "virtual" (descontado do que já foi planejado),
 * validades, histórico recente de receitas e o que já será necessário em outras refeições.
 */
class ContextoPlanejamento
{
    /**
     * @param  array<int, float>  $estoque  ingrediente_id => quantidade disponível (unidade base)
     * @param  array<int, int>  $validade  ingrediente_id => dias até o lote que vence primeiro
     * @param  Collection<int, Ingrediente>  $ingredientes
     * @param  array<int, array<int, array{id: int, compatibilidade: int}>>  $substituicoes  ingrediente_id => substitutos
     * @param  array<int, array{data: Carbon, receita_id: int, principal: ?int}>  $usos
     * @param  array<int, float>  $planejado  ingrediente_id => quantidade já necessária em outras refeições
     * @param  array<int, float>  $feedback  receita_id => quanto gostaram (−1..1, média das últimas vezes)
     * @param  array<int, int>  $bloqueadas  receitas marcadas como "não fazer novamente"
     */
    public function __construct(
        public array $estoque,
        public array $validade,
        public Collection $ingredientes,
        public array $substituicoes,
        public array $usos = [],
        public array $planejado = [],
        public array $feedback = [],
        public array $bloqueadas = [],
    ) {}

    /** @var array<int, float> ingrediente_id => quantidade já comprometida com refeições da semana */
    public array $consumo = [];

    /** @var array<int, bool> refeições ignoradas ao avaliar (a que está sendo reavaliada) */
    public array $ignorados = [];

    /**
     * Quanto resta de um ingrediente depois do que a semana já comprometeu (nunca negativo).
     */
    public function disponivel(int $ingredienteId): float
    {
        return max(0.0, ($this->estoque[$ingredienteId] ?? 0.0) - ($this->consumo[$ingredienteId] ?? 0.0));
    }

    /**
     * Registra uma refeição já decidida: compromete estoque e entra no histórico de usos.
     *
     * @param  array<int, float>  $necessidades
     */
    public function registrar(Carbon $data, int $receitaId, array $necessidades, ?int $refeicaoId = null): void
    {
        foreach ($necessidades as $ingredienteId => $quantidade) {
            $this->consumo[$ingredienteId] = ($this->consumo[$ingredienteId] ?? 0.0) + $quantidade;
            $this->planejado[$ingredienteId] = ($this->planejado[$ingredienteId] ?? 0.0) + $quantidade;
        }

        $this->usos[] = ['data' => $data, 'receita_id' => $receitaId, 'principal' => self::principal($necessidades), 'refeicao_id' => $refeicaoId];
    }

    /**
     * Registra uma refeição inteira (receita principal e itens extras, como os da lancheira).
     *
     * @param  array<int, float>|null  $necessidades  já calculadas (evita recalcular)
     */
    public function registrarRefeicao(RefeicaoPlanejada $refeicao, ?array $necessidades = null): void
    {
        $this->registrar($refeicao->data, $refeicao->receita_id, $necessidades ?? $refeicao->necessidades(), $refeicao->id);

        foreach ($refeicao->itens as $item) {
            $this->usos[] = ['data' => $refeicao->data, 'receita_id' => $item->receita_id, 'principal' => null, 'refeicao_id' => $refeicao->id];
        }
    }

    /**
     * Desfaz o efeito de uma refeição já registrada (para reavaliá-la sem ela).
     *
     * @param  array<int, float>  $necessidades
     */
    public function retirar(int $refeicaoId, array $necessidades): void
    {
        foreach ($necessidades as $ingredienteId => $quantidade) {
            $this->consumo[$ingredienteId] = ($this->consumo[$ingredienteId] ?? 0.0) - $quantidade;
            $this->planejado[$ingredienteId] = ($this->planejado[$ingredienteId] ?? 0.0) - $quantidade;
        }

        $this->ignorados[$refeicaoId] = true;
    }

    /**
     * Ingrediente em maior quantidade (proxy do "ingrediente principal" da receita).
     *
     * @param  array<int, float>  $necessidades
     */
    public static function principal(array $necessidades): ?int
    {
        if ($necessidades === []) {
            return null;
        }

        arsort($necessidades);

        return array_key_first($necessidades);
    }
}
