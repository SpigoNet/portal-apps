@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
<x-Cardapio::layout :titulo="$receita->nome">
    <div>
        <a class="text-sm underline" href="{{ route('cardapio.receitas.index') }}">← Receitas</a>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold">{{ $receita->favorita ? '⭐ ' : '' }}{{ $receita->nome }}</h1>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('cardapio.receitas.favoritar', $receita) }}">@csrf
                    <button class="cd-btn cd-btn-sec" type="submit">{{ $receita->favorita ? 'Desfavoritar' : '⭐ Favoritar' }}</button>
                </form>
                <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.receitas.edit', $receita) }}">Editar</a>
            </div>
        </div>
        @if ($receita->descricao) <p class="mt-1">{{ $receita->descricao }}</p> @endif
        <div class="flex flex-wrap gap-1 mt-2">
            @if ($receita->tempo_preparo) <span class="cd-chip">{{ $receita->tempo_preparo }} min</span> @endif
            <span class="cd-chip">{{ \App\Modules\Cardapio\Models\Receita::DIFICULDADES[$receita->dificuldade] }}</span>
            <span class="cd-chip">Frequência {{ strtolower(\App\Modules\Cardapio\Models\Receita::FREQUENCIAS[$receita->frequencia]) }}</span>
            @foreach ($receita->refeicoes_compativeis ?? [] as $tipo) <span class="cd-chip">{{ $rotulos[$tipo] ?? $tipo }}</span> @endforeach
            @foreach ($receita->categorias ?? [] as $categoria) <span class="cd-chip">{{ $categoria }}</span> @endforeach
        </div>
    </div>

    <section class="cd-card space-y-2">
        <form method="GET" class="flex items-center gap-2">
            <h2 class="font-extrabold text-lg mr-auto">Ingredientes</h2>
            <label class="text-sm font-semibold" for="porcoes">Porções</label>
            <input class="cd-input" style="width:5rem" id="porcoes" type="number" min="1" name="porcoes" value="{{ $porcoes }}">
            <button class="cd-btn cd-btn-sec" type="submit">Calcular</button>
        </form>
        <ul class="divide-y divide-green-100">
            @foreach ($receita->ingredientes as $linha)
                <li class="py-1.5">
                    {{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($linha->quantidade * $fator, $linha->ingrediente->unidade_base) }}
                    de {{ $linha->ingrediente->nome }}
                    @if ($linha->opcional) <span class="cd-chip">opcional</span> @endif
                </li>
            @endforeach
        </ul>
    </section>

    @if ($feitas->isNotEmpty())
        @php($valores = $feitas->map(fn ($h) => $h->valor())->filter(fn ($v) => $v !== null))
        <section class="cd-card text-sm">
            Feita <strong>{{ $feitas->count() }}</strong> vez(es); a última em {{ $feitas->first()->data->format('d/m/Y') }}.
            @if ($valores->isNotEmpty()) Aceitação média: <strong>{{ number_format(($valores->avg() + 1) * 50, 0) }}%</strong>. @endif
            <a class="underline font-bold" href="{{ route('cardapio.historico.index') }}">Ver histórico</a>
        </section>
    @endif

    @if ($receita->modo_preparo)
        <section class="cd-card">
            <h2 class="font-extrabold text-lg mb-2">Modo de preparo</h2>
            <div class="whitespace-pre-line">{{ $receita->modo_preparo }}</div>
        </section>
    @endif

    <form method="POST" action="{{ route('cardapio.receitas.destroy', $receita) }}" onsubmit="return confirm('Remover esta receita?')">
        @csrf @method('DELETE')
        <button class="cd-btn cd-btn-perigo" type="submit">Remover receita</button>
    </form>
</x-Cardapio::layout>
