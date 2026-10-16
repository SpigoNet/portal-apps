@php($unRotulos = \App\Modules\Cardapio\Support\UnidadeConversor::rotulos())
<x-Cardapio::layout titulo="Sobras">
    <h1 class="text-2xl font-extrabold">Sobras</h1>
    <p class="text-sm">Sobra vira estoque com validade curta (“Sobra de …”). Como vence logo, receitas que a usam ganham prioridade nas sugestões.</p>

    <section class="cd-card space-y-2">
        <h2 class="font-extrabold text-lg">Em casa agora</h2>
        @forelse ($lotes as $lote)
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-green-100 pt-2 first:border-0 first:pt-0">
                <div>
                    <span class="font-bold">{{ $lote->ingrediente->nome }}</span>
                    — {{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($lote->quantidade, $lote->ingrediente->unidade_base) }}
                    <span class="cd-chip {{ $lote->statusValidade() === 'normal' ? '' : 'cd-chip-aviso' }}">vence {{ $lote->validade->format('d/m') }}</span>
                </div>
                <form method="POST" action="{{ route('cardapio.sobras.destroy', $lote) }}" onsubmit="return confirm('Descartar esta sobra?')">@csrf @method('DELETE')
                    <button class="text-sm underline text-red-700" type="submit">descartar</button>
                </form>
            </div>
        @empty
            <p class="text-sm">Nenhuma sobra no momento.</p>
        @endforelse
    </section>

    <form method="POST" action="{{ route('cardapio.sobras.store') }}" class="cd-card space-y-3">
        @csrf
        <h2 class="font-extrabold text-lg">Registrar sobra</h2>
        @if ($refeicao)
            <input type="hidden" name="refeicao_id" value="{{ $refeicao->id }}">
            <p class="text-sm">Da refeição de {{ $refeicao->data->format('d/m') }}.</p>
        @endif
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="cd-label">Receita</label>
                <select class="cd-input" name="receita_id">
                    <option value="">— outra coisa —</option>
                    @foreach ($receitas as $r) <option value="{{ $r->id }}" @selected(old('receita_id', $refeicao?->receita_id) == $r->id)>{{ $r->nome }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="cd-label">Ou descreva</label>
                <input class="cd-input" name="nome" value="{{ old('nome') }}" maxlength="120" placeholder="ex.: arroz branco">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div><label class="cd-label">Quantidade</label><input class="cd-input" type="number" step="any" min="0" name="quantidade" value="{{ old('quantidade') }}" required></div>
            <div><label class="cd-label">Unidade</label>
                <select class="cd-input" name="unidade">
                    @foreach (['g', 'kg', 'ml', 'l', 'un'] as $u) <option value="{{ $u }}" @selected(old('unidade', 'g') === $u)>{{ $unRotulos[$u] }}</option> @endforeach
                </select></div>
            <div><label class="cd-label">Dura (dias)</label><input class="cd-input" type="number" min="1" max="30" name="dias" value="{{ old('dias', 2) }}" required></div>
        </div>
        <button class="cd-btn" type="submit">Guardar sobra</button>
    </form>

    @if ($registradas->isNotEmpty())
        <section class="cd-card text-sm">
            <h2 class="font-extrabold text-lg mb-1">Registradas recentemente</h2>
            @foreach ($registradas as $s)
                <div>{{ $s->created_at->format('d/m') }} · {{ $s->ingrediente->nome }} · {{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($s->quantidade, $s->ingrediente->unidade_base) }}</div>
            @endforeach
        </section>
    @endif
</x-Cardapio::layout>
