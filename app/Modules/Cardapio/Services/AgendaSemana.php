<?php

namespace App\Modules\Cardapio\Services;

use App\Modules\Cardapio\Models\Casa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Quem come o quê em cada dia: rotina − ausências, mais convidados, menos refeições fora de casa.
 */
class AgendaSemana
{
    /**
     * @param  Collection<string, Collection>  $regras  "dia_iso|tipo" => regras de rotina
     * @param  Collection<int, \App\Modules\Cardapio\Models\Evento>  $eventos
     * @param  Collection<int, \App\Modules\Cardapio\Models\Ausencia>  $ausencias
     */
    public function __construct(
        private readonly Collection $regras,
        private readonly Collection $eventos,
        private readonly Collection $ausencias,
    ) {}

    public static function carregar(Casa $casa, Carbon $inicio, Carbon $fim): self
    {
        $ativas = $casa->pessoas()->where('ativo', true)->pluck('id')->all();

        return new self(
            $casa->regrasRotina()->where('ativo', true)->whereIn('pessoa_id', $ativas)->get()
                ->groupBy(fn ($r) => $r->dia_semana.'|'.$r->tipo_refeicao),
            $casa->eventos()->whereDate('data', '>=', $inicio)->whereDate('data', '<=', $fim)->get(),
            $casa->ausencias()->whereDate('inicio', '<=', $fim)->whereDate('fim', '>=', $inicio)->get(),
        );
    }

    /**
     * @return array{pessoas: array<int, int>, extras: int}|null null quando a refeição não deve ser planejada
     */
    public function slot(Carbon $data, string $tipo): ?array
    {
        $doDia = $this->eventos->filter(fn ($e) => $e->data->isSameDay($data) && ($e->tipo_refeicao === null || $e->tipo_refeicao === $tipo));

        if ($doDia->contains('tipo', 'fora')) {
            return null;
        }

        $ausentes = $this->ausencias
            ->filter(fn ($a) => $a->inicio->startOfDay()->lte($data) && $a->fim->endOfDay()->gte($data))
            ->pluck('pessoa_id');

        $pessoas = ($this->regras->get($data->dayOfWeekIso.'|'.$tipo)?->pluck('pessoa_id')->unique() ?? collect())
            ->reject(fn ($id) => $ausentes->contains($id))
            ->values()->all();

        $extras = (int) $doDia->where('tipo', 'convidados')->sum('pessoas_extras');

        return $pessoas === [] && $extras === 0 ? null : ['pessoas' => $pessoas, 'extras' => $extras];
    }

    /**
     * @return Collection<int, int> ids das pessoas ausentes no dia
     */
    public function ausentes(Carbon $data): Collection
    {
        return $this->ausencias
            ->filter(fn ($a) => $a->inicio->startOfDay()->lte($data) && $a->fim->endOfDay()->gte($data))
            ->pluck('pessoa_id');
    }
}
