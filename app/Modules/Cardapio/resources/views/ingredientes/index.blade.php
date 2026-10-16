<x-Cardapio::layout titulo="Ingredientes">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">Ingredientes</h1>
        <a class="cd-btn" href="{{ route('cardapio.ingredientes.create') }}">+ Novo ingrediente</a>
    </div>

    @forelse ($ingredientes->groupBy(fn ($i) => $i->categoria?->nome ?? 'Sem categoria') as $categoria => $itens)
        <div class="cd-card">
            <div class="font-extrabold mb-2">{{ $categoria }}</div>
            <ul class="divide-y divide-green-100">
                @foreach ($itens as $ingrediente)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $ingrediente->nome }} <span class="cd-chip">{{ $ingrediente->unidade_base === 'un' ? 'unidades' : $ingrediente->unidade_base }}</span></span>
                        <a class="underline text-sm font-semibold" href="{{ route('cardapio.ingredientes.edit', $ingrediente) }}">Editar</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="cd-card">Nenhum ingrediente ainda. Cadastre os alimentos que você usa: eles alimentam estoque, receitas e preferências.</div>
    @endforelse
</x-Cardapio::layout>
