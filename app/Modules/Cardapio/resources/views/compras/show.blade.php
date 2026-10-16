@php($unRotulos = \App\Modules\Cardapio\Support\UnidadeConversor::rotulos())
@php($locais = \App\Modules\Cardapio\Models\ItemEstoque::LOCAIS)
<x-Cardapio::layout :titulo="$lista->titulo ?? 'Lista de compras'">
    <div>
        <a class="text-sm underline" href="{{ route('cardapio.compras.index') }}">← Listas</a>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold">🛒 {{ $lista->titulo ?? 'Lista de compras' }}</h1>
            <form method="POST" action="{{ route('cardapio.compras.concluir', $lista) }}">@csrf
                <button class="cd-btn cd-btn-sec" type="submit">{{ $lista->status === 'aberta' ? 'Concluir lista' : 'Reabrir lista' }}</button>
            </form>
        </div>
    </div>

    @forelse ($grupos as $categoria => $itens)
        <section class="cd-card">
            <h2 class="font-extrabold mb-2">{{ $categoria }}</h2>
            <ul class="divide-y divide-green-100">
                @foreach ($itens as $item)
                    <li class="py-2 flex flex-wrap items-center gap-3 {{ $item->comprado ? 'opacity-60' : '' }}">
                        <form method="POST" action="{{ route('cardapio.compras.itens.comprado', [$lista, $item]) }}">@csrf
                            <button type="submit" class="w-10 h-10 rounded-lg border-2 border-green-600 font-bold {{ $item->comprado ? 'bg-green-600 text-white' : 'bg-white' }}" title="Marcar como comprado">{{ $item->comprado ? '✓' : '' }}</button>
                        </form>
                        <div class="flex-1" style="min-width:10rem">
                            <div class="font-bold {{ $item->comprado ? 'line-through' : '' }}">{{ $item->nome() }}</div>
                            @if ($item->ingrediente)
                                <div class="text-sm">
                                    Necessário {{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($item->quantidade_necessaria, $item->ingrediente->unidade_base) }}
                                    · Em estoque {{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($item->quantidade_estoque, $item->ingrediente->unidade_base) }}
                                    · <strong>Comprar {{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($item->quantidade_comprar, $item->ingrediente->unidade_base) }}</strong>
                                    @if ($item->adicionado_ao_estoque) <span class="cd-chip">no estoque</span> @endif
                                </div>
                            @endif
                        </div>
                        @if ($item->ingrediente && ! $item->adicionado_ao_estoque)
                            @php([$v, $u] = \App\Modules\Cardapio\Support\UnidadeConversor::legivel($item->quantidade_comprar, $item->ingrediente->unidade_base))
                            <form method="POST" action="{{ route('cardapio.compras.itens.quantidade', [$lista, $item]) }}" class="flex gap-1 cd-fluido">@csrf
                                <input class="cd-input" style="width:5rem" inputmode="decimal" type="number" step="any" min="0" name="quantidade" value="{{ round($v, 3) }}">
                                <select class="cd-input" style="width:6rem" name="unidade">
                                    @foreach ($unRotulos as $chave => $rotulo)
                                        @if (\App\Modules\Cardapio\Support\UnidadeConversor::compativel($chave, $item->ingrediente->unidade_base))
                                            <option value="{{ $chave }}" @selected($chave === $u)>{{ $rotulo }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button class="cd-btn cd-btn-sec" type="submit">OK</button>
                            </form>
                        @endif
                        @unless ($item->comprado)
                            <form method="POST" action="{{ route('cardapio.compras.itens.mover', [$lista, $item]) }}">@csrf
                                <button class="text-sm underline" type="submit" title="Passar para a próxima compra">próxima compra</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('cardapio.compras.itens.destroy', [$lista, $item]) }}">@csrf @method('DELETE')
                            <button class="text-sm underline text-red-700" type="submit">tirar</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="cd-card">Nada para comprar. Se o cardápio já está coberto pelo estoque, a lista fica vazia.</div>
    @endforelse

    <form method="POST" action="{{ route('cardapio.compras.itens.store', $lista) }}" class="cd-card flex gap-2">
        @csrf
        <input class="cd-input" name="descricao" placeholder="+ Adicionar item (ex.: chocolate para o fim de semana)" required maxlength="160">
        <button class="cd-btn" type="submit">Adicionar</button>
    </form>

    @if ($paraAdicionar > 0)
        <form method="POST" action="{{ route('cardapio.compras.estoque', $lista) }}" class="cd-card flex flex-wrap items-center gap-2">
            @csrf
            <span class="font-bold">{{ $paraAdicionar }} item(ns) comprados ainda fora do estoque</span>
            <select class="cd-input" style="width:9rem" name="local">
                @foreach ($locais as $chave => $rotulo) <option value="{{ $chave }}">{{ $rotulo }}</option> @endforeach
            </select>
            <button class="cd-btn" type="submit">Adicionar tudo ao estoque</button>
        </form>
    @endif
</x-Cardapio::layout>
