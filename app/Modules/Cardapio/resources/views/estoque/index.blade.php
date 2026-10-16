@php($locais = \App\Modules\Cardapio\Models\ItemEstoque::LOCAIS)
@php($unidades = \App\Modules\Cardapio\Support\UnidadeConversor::rotulos())
@php($status = ['normal' => ['Normal', ''], 'atencao' => ['Atenção', 'cd-chip-aviso'], 'urgente' => ['Urgente', 'cd-chip-aviso'], 'vencido' => ['Vencido', 'cd-chip-aviso']])
<x-Cardapio::layout titulo="Estoque">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">Estoque</h1>
        <a class="cd-btn" href="{{ route('cardapio.estoque.create') }}">+ Adicionar</a>
    </div>

    @if ($vencendo->isNotEmpty() || $estoqueBaixo->isNotEmpty())
        <div class="cd-card space-y-1 text-sm" style="border-color:#fcd34d; background:#fffbeb">
            @foreach ($vencendo as $v)
                <div>⚠ <strong>{{ $v->ingrediente->nome }}</strong> —
                    @if ($v->diasParaVencer() < 0) venceu há {{ abs($v->diasParaVencer()) }} dia(s)
                    @elseif ($v->diasParaVencer() === 0) vence hoje
                    @else vence em {{ $v->diasParaVencer() }} dia(s) @endif
                </div>
            @endforeach
            @foreach ($estoqueBaixo as $linha)
                <div>📉 <strong>{{ $linha['ingrediente']->nome }}</strong> — estoque baixo
                    ({{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($linha['total'], $linha['ingrediente']->unidade_base) }}
                    de mínimo {{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($linha['minimo'], $linha['ingrediente']->unidade_base) }})</div>
            @endforeach
        </div>
    @endif

    <form method="GET" class="flex flex-wrap gap-2 items-center">
        <a href="{{ route('cardapio.estoque.index', ['q' => $busca]) }}" class="cd-chip {{ $local ? '' : 'cd-btn' }}" style="padding:.35rem .9rem">Todos</a>
        @foreach ($locais as $chave => $rotulo)
            <a href="{{ route('cardapio.estoque.index', ['local' => $chave, 'q' => $busca]) }}"
               class="cd-chip" style="padding:.35rem .9rem; {{ $local === $chave ? 'background:#166534;color:#fff' : '' }}">{{ $rotulo }}</a>
        @endforeach
        @if ($local) <input type="hidden" name="local" value="{{ $local }}"> @endif
        <input class="cd-input cd-fluido" style="max-width:16rem" type="search" name="q" value="{{ $busca }}" placeholder="🔎 Buscar alimento">
    </form>

    <div class="space-y-2">
        @forelse ($itens as $item)
            <div class="cd-card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-extrabold text-lg">{{ $item->ingrediente->nome }} @if ($item->ingrediente->sobra) <span class="cd-chip">sobra</span> @endif</div>
                    <div class="text-sm">
                        {{ $item->aproximada ? '≈ ' : '' }}{{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($item->quantidade, $item->ingrediente->unidade_base) }}
                        · {{ $locais[$item->local] }}@if ($item->marca) · {{ $item->marca }} @endif
                    </div>
                    <div class="flex gap-1 mt-1">
                        @if ($item->validade)
                            <span class="cd-chip {{ $status[$item->statusValidade()][1] }}">{{ $status[$item->statusValidade()][0] }} · {{ $item->validade->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 cd-fluido">
                    <form method="POST" action="{{ route('cardapio.estoque.baixar', $item) }}" class="flex gap-1 cd-fluido">
                        @csrf
                        <input class="cd-input" style="width:5rem" inputmode="decimal" type="number" step="any" min="0" name="quantidade" placeholder="usei" required>
                        <select class="cd-input" style="width:6rem" name="unidade">
                            @foreach ($unidades as $chave => $rotulo)
                                @if (\App\Modules\Cardapio\Support\UnidadeConversor::compativel($chave, $item->ingrediente->unidade_base))
                                    <option value="{{ $chave }}" @selected($chave === \App\Modules\Cardapio\Support\UnidadeConversor::legivel($item->quantidade, $item->ingrediente->unidade_base)[1])>{{ $rotulo }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button class="cd-btn cd-btn-sec" type="submit">Usar</button>
                    </form>
                    <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.estoque.edit', $item) }}">Editar</a>
                </div>
            </div>
        @empty
            <div class="cd-card">Nada no estoque{{ $busca || $local ? ' com esse filtro' : '' }}. Comece em “+ Adicionar”.</div>
        @endforelse
    </div>
</x-Cardapio::layout>
