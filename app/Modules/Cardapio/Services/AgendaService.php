<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Models\Ausencia;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Evento;
use App\Modules\Cardapio\Models\Pessoa;
use Illuminate\Support\Carbon;

/**
 * Eventos e ausências: grava e reaplica na grade das semanas já planejadas (e na lista de compras aberta).
 */
class AgendaService
{
    public function __construct(
        private readonly PlanejadorService $planejador,
        private readonly ComprasService $compras,
    ) {}

    /**
     * @param  array{data: string, tipo: string, titulo: string, tipo_refeicao?: ?string, pessoas_extras?: ?int}  $dados
     */
    public function criarEvento(Casa $casa, array $dados): Evento
    {
        $evento = $casa->eventos()->create([
            'data' => $dados['data'],
            'tipo_refeicao' => ($dados['tipo_refeicao'] ?? null) ?: null,
            'tipo' => $dados['tipo'],
            'titulo' => $dados['titulo'],
            'pessoas_extras' => $dados['tipo'] === 'convidados' ? (int) ($dados['pessoas_extras'] ?? 0) : 0,
        ]);

        $this->reaplicar($casa, $evento->data->copy(), $evento->data->copy());

        return $evento;
    }

    public function removerEvento(Evento $evento): void
    {
        $casa = $evento->casa_id;
        $data = $evento->data->copy();
        $evento->delete();

        $this->reaplicar(Casa::findOrFail($casa), $data, $data->copy());
    }

    /**
     * @param  array{inicio: string, fim: string, motivo?: ?string}  $dados
     */
    public function criarAusencia(Casa $casa, Pessoa $pessoa, array $dados): Ausencia
    {
        $ausencia = $casa->ausencias()->create([
            'pessoa_id' => $pessoa->id,
            'inicio' => $dados['inicio'],
            'fim' => $dados['fim'],
            'motivo' => $dados['motivo'] ?? null,
        ]);

        $this->reaplicar($casa, $ausencia->inicio->copy(), $ausencia->fim->copy());

        return $ausencia;
    }

    public function removerAusencia(Ausencia $ausencia): void
    {
        [$casa, $inicio, $fim] = [$ausencia->casa_id, $ausencia->inicio->copy(), $ausencia->fim->copy()];
        $ausencia->delete();

        $this->reaplicar(Casa::findOrFail($casa), $inicio, $fim);
    }

    public function reaplicar(Casa $casa, Carbon $inicio, Carbon $fim): void
    {
        $this->planejador->sincronizarPeriodo($casa, $inicio, $fim);

        foreach ($casa->planos()->whereDate('inicio_semana', '<=', $fim)->whereDate('inicio_semana', '>=', PlanejadorService::segundaDaSemana($inicio))->get() as $plano) {
            if ($plano->listaCompras()->where('status', 'aberta')->exists()) {
                $this->compras->gerarParaPlano($plano);
            }
        }
    }
}
