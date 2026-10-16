@php($unRotulos = \App\Modules\Cardapio\Support\UnidadeConversor::rotulos())
<x-Cardapio::layout titulo="Produtos recorrentes">
    <div>
        <a class="text-sm underline" href="{{ route('cardapio.compras.index') }}">← Compras</a>
        <h1 class="text-2xl font-extrabold">Produtos recorrentes</h1>
        <p class="text-sm">Tudo que costuma entrar na compra. Com ingrediente, a lista só pede o que falta para a quantidade desejada; os itens livres entram uma vez por lista.</p>
    </div>

    <section class="cd-card">
        <ul class="divide-y divide-green-100">
            @forelse ($produtos as $p)
                <li class="py-2 flex flex-wrap items-center justify-between gap-2 {{ $p->ativo ? '' : 'opacity-50' }}">
                    <span class="font-bold">{{ $p->nome() }}
                        @if ($p->ingrediente) <span class="cd-chip">{{ \App\Modules\Cardapio\Support\UnidadeConversor::formatar($p->quantidade, $p->ingrediente->unidade_base) }}</span> @endif
                    </span>
                    <span class="flex gap-3 text-sm">
                        <form method="POST" action="{{ route('cardapio.recorrentes.alternar', $p) }}">@csrf <button class="underline" type="submit">{{ $p->ativo ? 'pausar' : 'ativar' }}</button></form>
                        <form method="POST" action="{{ route('cardapio.recorrentes.destroy', $p) }}">@csrf @method('DELETE') <button class="underline text-red-700" type="submit">remover</button></form>
                    </span>
                </li>
            @empty
                <li class="py-2 text-sm">Nada cadastrado ainda.</li>
            @endforelse
        </ul>
    </section>

    <form method="POST" action="{{ route('cardapio.recorrentes.store') }}" class="cd-card grid sm:grid-cols-5 gap-2 items-end">
        @csrf
        <div class="sm:col-span-2"><label class="cd-label">Ingrediente</label>
            <select class="cd-input" name="ingrediente_id">
                <option value="">— item livre —</option>
                @foreach ($ingredientes as $i) <option value="{{ $i->id }}" @selected(old('ingrediente_id') == $i->id)>{{ $i->nome }}</option> @endforeach
            </select></div>
        <div><label class="cd-label">Quantidade desejada</label><input class="cd-input" type="number" step="any" min="0" name="quantidade" value="{{ old('quantidade') }}"></div>
        <div><label class="cd-label">Unidade</label>
            <select class="cd-input" name="unidade">@foreach ($unRotulos as $k => $r) <option value="{{ $k }}" @selected(old('unidade', 'g') === $k)>{{ $r }}</option> @endforeach</select></div>
        <div class="sm:col-span-4"><label class="cd-label">Ou item livre</label><input class="cd-input" name="descricao" value="{{ old('descricao') }}" maxlength="160" placeholder="papel higiênico, detergente…"></div>
        <button class="cd-btn" type="submit">Adicionar</button>
    </form>
</x-Cardapio::layout>
