@php($tipos = \App\Modules\Cardapio\Models\Restricao::TIPOS)
@php($niveis = \App\Modules\Cardapio\Models\Preferencia::NIVEIS)
<x-Cardapio::layout :titulo="'Perfil de '.$pessoa->nome">
    <div>
        <a class="text-sm underline" href="{{ route('cardapio.pessoas.index') }}">← Pessoas</a>
        <h1 class="text-2xl font-extrabold">Gostos e restrições de {{ $pessoa->nome }}</h1>
    </div>

    @if ($ingredientes->isEmpty())
        <div class="cd-card">Cadastre ingredientes primeiro: <a class="underline font-bold" href="{{ route('cardapio.ingredientes.create') }}">novo ingrediente</a>.</div>
    @endif

    <section class="cd-card space-y-3">
        <h2 class="font-extrabold text-lg">Restrições alimentares</h2>
        <ul class="divide-y divide-green-100">
            @forelse ($restricoes as $restricao)
                <li class="flex items-center justify-between gap-2 py-2">
                    <span>
                        <span class="cd-chip {{ $restricao->tipo === 'alergia' ? 'cd-chip-aviso' : '' }}">{{ explode(' (', $tipos[$restricao->tipo])[0] }}</span>
                        {{ $restricao->ingrediente?->nome }} {{ $restricao->descricao }}
                    </span>
                    <form method="POST" action="{{ route('cardapio.perfil.restricoes.remover', [$pessoa, $restricao]) }}">
                        @csrf @method('DELETE')
                        <button class="text-sm underline text-red-700" type="submit">remover</button>
                    </form>
                </li>
            @empty
                <li class="py-2 text-sm">Nenhuma restrição cadastrada.</li>
            @endforelse
        </ul>

        <form method="POST" action="{{ route('cardapio.perfil.restricoes.salvar', $pessoa) }}" class="grid sm:grid-cols-4 gap-2 items-end">
            @csrf
            <div>
                <label class="cd-label">Tipo</label>
                <select class="cd-input" name="tipo">
                    @foreach ($tipos as $valor => $rotulo)
                        <option value="{{ $valor }}">{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="cd-label">Ingrediente</label>
                <select class="cd-input" name="ingrediente_id">
                    <option value="">— nenhum (objetivo) —</option>
                    @foreach ($ingredientes as $ingrediente)
                        <option value="{{ $ingrediente->id }}">{{ $ingrediente->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="cd-label">Descrição (objetivos)</label>
                <input class="cd-input" name="descricao" maxlength="255" placeholder="ex.: reduzir açúcar">
            </div>
            <button class="cd-btn" type="submit">Adicionar</button>
        </form>
    </section>

    <section class="cd-card space-y-3">
        <h2 class="font-extrabold text-lg">Preferências</h2>
        <ul class="divide-y divide-green-100">
            @forelse ($preferencias as $preferencia)
                <li class="flex items-center justify-between gap-2 py-2">
                    <span><span class="cd-chip {{ $preferencia->nivel < 0 ? 'cd-chip-aviso' : '' }}">{{ $niveis[$preferencia->nivel] ?? $preferencia->nivel }}</span> {{ $preferencia->ingrediente->nome }}</span>
                    <form method="POST" action="{{ route('cardapio.perfil.preferencias.remover', [$pessoa, $preferencia]) }}">
                        @csrf @method('DELETE')
                        <button class="text-sm underline text-red-700" type="submit">remover</button>
                    </form>
                </li>
            @empty
                <li class="py-2 text-sm">Nenhuma preferência cadastrada.</li>
            @endforelse
        </ul>

        <form method="POST" action="{{ route('cardapio.perfil.preferencias.salvar', $pessoa) }}" class="grid sm:grid-cols-3 gap-2 items-end">
            @csrf
            <div>
                <label class="cd-label">Ingrediente</label>
                <select class="cd-input" name="ingrediente_id" required>
                    @foreach ($ingredientes as $ingrediente)
                        <option value="{{ $ingrediente->id }}">{{ $ingrediente->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="cd-label">Nível</label>
                <select class="cd-input" name="nivel">
                    @foreach ($niveis as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($valor === 3)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <button class="cd-btn" type="submit">Salvar</button>
        </form>
    </section>
</x-Cardapio::layout>
