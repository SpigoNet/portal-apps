<x-Cardapio::layout titulo="Compras">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">Listas de compras</h1>
        <form method="POST" action="{{ route('cardapio.compras.store') }}">@csrf
            <button class="cd-btn" type="submit">+ Lista avulsa</button>
        </form>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('cardapio.compras.recorrentes') }}">@csrf
            <button class="cd-btn cd-btn-sec" type="submit">♻ Lista dos recorrentes</button>
        </form>
        <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.recorrentes.index') }}">Gerenciar recorrentes</a>
    </div>
    <p class="text-sm">As listas da semana nascem em <a class="underline font-bold" href="{{ route('cardapio.planejamento.index') }}">Planejamento → Calcular compras</a>.</p>

    <div class="space-y-2">
        @forelse ($listas as $lista)
            <a href="{{ route('cardapio.compras.show', $lista) }}" class="cd-card block hover:border-green-500 {{ $lista->status === 'aberta' ? '' : 'opacity-60' }}">
                <div class="font-extrabold">{{ $lista->titulo ?? 'Lista #'.$lista->id }}</div>
                <div class="text-sm">{{ $lista->pendentes_count }} pendentes de {{ $lista->itens_count }} · {{ $lista->status === 'aberta' ? 'aberta' : 'concluída' }}</div>
            </a>
        @empty
            <div class="cd-card">Nenhuma lista ainda.</div>
        @endforelse
    </div>
</x-Cardapio::layout>
