<x-Cardapio::layout titulo="Receitas">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">Receitas</h1>
        <a class="cd-btn" href="{{ route('cardapio.receitas.create') }}">+ Nova receita</a>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 items-center">
        <input class="cd-input cd-fluido" style="max-width:16rem" type="search" name="q" value="{{ $busca }}" placeholder="🔎 Buscar receita">
        <label class="flex items-center gap-2 font-semibold"><input type="checkbox" name="favoritas" value="1" @checked($soFavoritas) onchange="this.form.submit()"> Só favoritas</label>
    </form>

    <div class="grid sm:grid-cols-2 gap-3">
        @forelse ($receitas as $receita)
            <a href="{{ route('cardapio.receitas.show', $receita) }}" class="cd-card block hover:border-green-500">
                <div class="font-extrabold text-lg">{{ $receita->favorita ? '⭐ ' : '' }}{{ $receita->nome }}</div>
                <div class="flex flex-wrap gap-1 mt-1">
                    <span class="cd-chip">{{ $receita->rendimento }} porções</span>
                    @if ($receita->tempo_preparo) <span class="cd-chip">{{ $receita->tempo_preparo }} min</span> @endif
                    <span class="cd-chip">{{ \App\Modules\Cardapio\Models\Receita::DIFICULDADES[$receita->dificuldade] }}</span>
                    @foreach ($receita->categorias ?? [] as $categoria) <span class="cd-chip">{{ $categoria }}</span> @endforeach
                </div>
            </a>
        @empty
            <div class="cd-card sm:col-span-2">Nenhuma receita{{ $busca || $soFavoritas ? ' com esse filtro' : ' ainda' }}.</div>
        @endforelse
    </div>
</x-Cardapio::layout>
