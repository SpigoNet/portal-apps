@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
@php($feedbacks = \App\Modules\Cardapio\Models\HistoricoRefeicao::FEEDBACKS)
<x-Cardapio::layout titulo="Histórico">
    <h1 class="text-2xl font-extrabold">Histórico de refeições</h1>

    @if ($rejeitadas->isNotEmpty())
        <div class="cd-card text-sm" style="border-color:#fcd34d; background:#fffbeb">
            🚫 Não fazer novamente: <strong>{{ $rejeitadas->pluck('receita.nome')->join(', ') }}</strong> — elas não entram mais nas sugestões automáticas.
        </div>
    @endif

    @if ($receitas->isNotEmpty())
        <section class="cd-card">
            <h2 class="font-extrabold text-lg mb-2">Mais feitas</h2>
            <ul class="divide-y divide-green-100">
                @foreach ($receitas->take(8) as $linha)
                    <li class="py-2 flex flex-wrap items-center justify-between gap-2">
                        <span class="font-bold">{{ $linha['receita']?->nome ?? 'Receita removida' }}</span>
                        <span class="text-sm">{{ $linha['vezes'] }}× · última em {{ $linha['ultima']->format('d/m') }}
                            @if ($linha['media'] !== null) · aceitação {{ number_format(($linha['media'] + 1) * 50, 0) }}% @endif</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="space-y-3">
        @forelse ($historicos as $h)
            <div class="cd-card space-y-2" id="h{{ $h->id }}">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <div class="font-extrabold">{{ $h->receita?->nome ?? 'Receita removida' }}</div>
                    <div class="text-sm">{{ $rotulos[$h->tipo_refeicao] ?? $h->tipo_refeicao }} · {{ $h->data->format('d/m/Y') }} · {{ $h->qtd_pessoas }} pessoa(s)</div>
                </div>
                @if ($h->consumo)
                    <div class="text-xs">Baixado do estoque:
                        {{ collect($h->consumo)->filter(fn ($q) => $q > 0)->map(fn ($q, $id) => isset($ingredientes[$id]) ? $ingredientes[$id]->nome.' '.\App\Modules\Cardapio\Support\UnidadeConversor::formatar($q, $ingredientes[$id]->unidade_base) : '?')->join(', ') ?: 'nada (sem estoque)' }}
                    </div>
                @endif
                <form method="POST" action="{{ route('cardapio.historico.feedback', $h) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <select class="cd-input cd-fluido" style="width:14rem" name="feedback">
                        <option value="">Como foi?</option>
                        @foreach ($feedbacks as $valor => [$rotulo])
                            <option value="{{ $valor }}" @selected($h->feedback === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    <select class="cd-input cd-fluido" style="width:7rem" name="nota">
                        <option value="">Nota</option>
                        @for ($n = 5; $n >= 1; $n--)
                            <option value="{{ $n }}" @selected($h->nota === $n)>{{ str_repeat('★', $n) }}</option>
                        @endfor
                    </select>
                    <button class="cd-btn cd-btn-sec" type="submit">Salvar</button>
                </form>
            </div>
        @empty
            <div class="cd-card">Nada por aqui ainda. Quando você marcar uma refeição como “Feita” no planejamento, ela aparece aqui para você avaliar.</div>
        @endforelse
    </section>
</x-Cardapio::layout>
