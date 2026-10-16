<x-Cardapio::layout titulo="Planejamento">
    <h1 class="text-2xl font-extrabold">Planejamento semanal</h1>

    <div class="cd-card space-y-3">
        <div class="font-bold">Planejar uma semana</div>
        <p class="text-sm">A grade é montada pela rotina da casa (quem come cada refeição); depois você escolhe as receitas.</p>
        <div class="flex flex-wrap gap-2">
            @foreach ([['Esta semana', $semanaAtual], ['Próxima semana', $proximaSemana]] as [$rotulo, $data])
                <form method="POST" action="{{ route('cardapio.planejamento.store') }}">
                    @csrf
                    <input type="hidden" name="semana" value="{{ $data->toDateString() }}">
                    <button class="cd-btn" type="submit">{{ $rotulo }} ({{ $data->format('d/m') }})</button>
                </form>
            @endforeach
            <form method="POST" action="{{ route('cardapio.planejamento.store') }}" class="flex gap-2">
                @csrf
                <input class="cd-input" type="date" name="semana" required>
                <button class="cd-btn cd-btn-sec" type="submit">Outra semana</button>
            </form>
        </div>
    </div>

    <div class="space-y-2">
        @forelse ($planos as $plano)
            <a href="{{ route('cardapio.planejamento.show', $plano) }}" class="cd-card block hover:border-green-500">
                <div class="font-extrabold">{{ $plano->inicio_semana->format('d/m/Y') }} a {{ $plano->fimSemana()->format('d/m/Y') }}</div>
                <div class="text-sm">{{ $plano->refeicoes_count }} refeições</div>
            </a>
        @empty
            <div class="cd-card">Nenhuma semana planejada ainda.</div>
        @endforelse
    </div>
</x-Cardapio::layout>
