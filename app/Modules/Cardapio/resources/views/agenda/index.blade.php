@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
<x-Cardapio::layout titulo="Agenda">
    <h1 class="text-2xl font-extrabold">Agenda</h1>
    <p class="text-sm">Eventos e ausências ajustam o cardápio: refeição fora de casa deixa de ser planejada, convidados aumentam as quantidades e quem está ausente sai do cálculo.</p>

    <section class="cd-card space-y-3">
        <h2 class="font-extrabold text-lg">Eventos</h2>
        <ul class="divide-y divide-green-100">
            @forelse ($eventos as $e)
                <li class="py-2 flex flex-wrap items-center justify-between gap-2">
                    <span>
                        <strong>{{ $e->data->format('d/m/Y') }}</strong> — {{ $e->titulo }}
                        <span class="cd-chip">{{ $e->tipo_refeicao ? $rotulos[$e->tipo_refeicao] : 'o dia todo' }}</span>
                        @if ($e->tipo === 'fora') <span class="cd-chip cd-chip-aviso">fora de casa</span>
                        @else <span class="cd-chip">+{{ $e->pessoas_extras }} pessoa(s)</span> @endif
                    </span>
                    <form method="POST" action="{{ route('cardapio.agenda.eventos.destroy', $e) }}">@csrf @method('DELETE')
                        <button class="text-sm underline text-red-700" type="submit">remover</button>
                    </form>
                </li>
            @empty
                <li class="py-2 text-sm">Nenhum evento próximo.</li>
            @endforelse
        </ul>

        <form method="POST" action="{{ route('cardapio.agenda.eventos.store') }}" class="grid sm:grid-cols-7 gap-2 items-end"
              x-data="{ tipo: '{{ old('tipo', 'fora') }}' }">
            @csrf
            <div class="sm:col-span-2"><label class="cd-label">Título</label><input class="cd-input" name="titulo" value="{{ old('titulo') }}" placeholder="ex.: Churrasco, almoço na casa dos avós" required></div>
            <div><label class="cd-label">Data</label><input class="cd-input" type="date" name="data" value="{{ old('data') }}" required></div>
            <div><label class="cd-label">Refeição</label>
                <select class="cd-input" name="tipo_refeicao">
                    <option value="">O dia todo</option>
                    @foreach ($rotulos as $t => $r) <option value="{{ $t }}" @selected(old('tipo_refeicao') === $t)>{{ $r }}</option> @endforeach
                </select></div>
            <div class="sm:col-span-2"><label class="cd-label">Tipo</label>
                <select class="cd-input" name="tipo" x-model="tipo">
                    @foreach (\App\Modules\Cardapio\Models\Evento::TIPOS as $v => $r) <option value="{{ $v }}">{{ $r }}</option> @endforeach
                </select></div>
            <div x-show="tipo === 'convidados'" x-cloak><label class="cd-label">Pessoas a mais</label><input class="cd-input" type="number" min="1" name="pessoas_extras" value="{{ old('pessoas_extras') }}"></div>
            <button class="cd-btn sm:col-span-7 sm:w-48" type="submit">Adicionar evento</button>
        </form>
    </section>

    <section class="cd-card space-y-3">
        <h2 class="font-extrabold text-lg">Ausências</h2>
        <ul class="divide-y divide-green-100">
            @forelse ($ausencias as $a)
                <li class="py-2 flex flex-wrap items-center justify-between gap-2">
                    <span><strong>{{ $a->pessoa->nome }}</strong> — {{ $a->inicio->format('d/m') }} a {{ $a->fim->format('d/m/Y') }} @if ($a->motivo) <span class="cd-chip">{{ $a->motivo }}</span> @endif</span>
                    <form method="POST" action="{{ route('cardapio.agenda.ausencias.destroy', $a) }}">@csrf @method('DELETE')
                        <button class="text-sm underline text-red-700" type="submit">remover</button>
                    </form>
                </li>
            @empty
                <li class="py-2 text-sm">Nenhuma ausência cadastrada.</li>
            @endforelse
        </ul>

        <form method="POST" action="{{ route('cardapio.agenda.ausencias.store') }}" class="grid sm:grid-cols-5 gap-2 items-end">
            @csrf
            <div><label class="cd-label">Pessoa</label>
                <select class="cd-input" name="pessoa_id">@foreach ($pessoas as $p) <option value="{{ $p->id }}">{{ $p->nome }}</option> @endforeach</select></div>
            <div><label class="cd-label">De</label><input class="cd-input" type="date" name="inicio" required></div>
            <div><label class="cd-label">Até</label><input class="cd-input" type="date" name="fim" required></div>
            <div><label class="cd-label">Motivo</label><input class="cd-input" name="motivo" placeholder="viagem…"></div>
            <button class="cd-btn" type="submit">Adicionar</button>
        </form>
    </section>
</x-Cardapio::layout>
