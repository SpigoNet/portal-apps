@php($editando = $item->exists)
@php($locais = \App\Modules\Cardapio\Models\ItemEstoque::LOCAIS)
@php($rotulos = \App\Modules\Cardapio\Support\UnidadeConversor::rotulos())
@php($bases = $ingredientes->pluck('unidade_base', 'id'))
@php($gruposUnidade = \App\Modules\Cardapio\Support\UnidadeConversor::porBase())
<x-Cardapio::layout :titulo="$editando ? 'Editar item' : 'Adicionar ao estoque'">
    <h1 class="text-2xl font-extrabold">{{ $editando ? 'Editar '.$item->ingrediente->nome : 'Adicionar ao estoque' }}</h1>

    @if ($ingredientes->isEmpty())
        <div class="cd-card">Cadastre o alimento antes em <a class="underline font-bold" href="{{ route('cardapio.ingredientes.create') }}">Ingredientes</a>.</div>
    @else
        <form method="POST" class="cd-card space-y-4"
              x-data="{ bases: @js($bases), grupos: @js($gruposUnidade), rotulos: @js($rotulos), ing: '{{ old('ingrediente_id', $item->ingrediente_id ?? $ingredientes->first()->id) }}', un: '{{ old('unidade', $unidade) }}',
                        get opcoes() { return this.grupos[this.bases[this.ing]] ?? []; },
                        ajusta() { if (!this.opcoes.includes(this.un)) this.un = this.opcoes[0]; } }"
              x-init="ajusta()"
              action="{{ $editando ? route('cardapio.estoque.update', $item) : route('cardapio.estoque.store') }}">
            @csrf
            @if ($editando) @method('PUT') @endif

            <div>
                <label class="cd-label" for="ingrediente_id">Alimento</label>
                <select class="cd-input" id="ingrediente_id" name="ingrediente_id" x-model="ing" @change="ajusta()">
                    @foreach ($ingredientes as $ingrediente)
                        <option value="{{ $ingrediente->id }}">{{ $ingrediente->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="cd-label" for="quantidade">Quantidade</label>
                    <input class="cd-input" id="quantidade" type="number" step="any" min="0" name="quantidade" value="{{ old('quantidade', $valor) }}" required>
                </div>
                <div>
                    <label class="cd-label" for="unidade">Unidade</label>
                    <select class="cd-input" id="unidade" name="unidade" x-model="un">
                        <template x-for="o in opcoes" :key="o"><option :value="o" x-text="rotulos[o]" :selected="o === un"></option></template>
                    </select>
                </div>
                <div>
                    <label class="cd-label" for="local">Local</label>
                    <select class="cd-input" id="local" name="local">
                        @foreach ($locais as $chave => $rotulo)
                            <option value="{{ $chave }}" @selected(old('local', $item->local) === $chave)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="cd-label" for="validade">Validade</label>
                    <input class="cd-input" id="validade" type="date" name="validade" value="{{ old('validade', $item->validade?->format('Y-m-d')) }}">
                </div>
            </div>

            <label class="flex items-center gap-2 font-semibold">
                <input type="checkbox" name="aproximada" value="1" @checked(old('aproximada', $item->aproximada))> Quantidade aproximada
            </label>

            <details class="text-sm" @if ($item->marca || $item->lote || $item->quantidade_minima || $item->quantidade_ideal || $item->observacoes) open @endif>
                <summary class="font-bold cursor-pointer">Mais detalhes</summary>
                <div class="grid sm:grid-cols-2 gap-3 mt-3">
                    <div><label class="cd-label">Marca</label><input class="cd-input" name="marca" value="{{ old('marca', $item->marca) }}"></div>
                    <div><label class="cd-label">Lote</label><input class="cd-input" name="lote" value="{{ old('lote', $item->lote) }}"></div>
                    <div><label class="cd-label">Quantidade mínima (na unidade escolhida)</label>
                        <input class="cd-input" type="number" step="any" min="0" name="minimo"
                               value="{{ old('minimo', $item->quantidade_minima ? \App\Modules\Cardapio\Support\UnidadeConversor::legivel($item->quantidade_minima, $item->ingrediente->unidade_base)[0] : '') }}"></div>
                    <div><label class="cd-label">Quantidade ideal (na unidade escolhida)</label>
                        <input class="cd-input" type="number" step="any" min="0" name="ideal"
                               value="{{ old('ideal', $item->quantidade_ideal ? \App\Modules\Cardapio\Support\UnidadeConversor::legivel($item->quantidade_ideal, $item->ingrediente->unidade_base)[0] : '') }}"></div>
                    <div class="sm:col-span-2"><label class="cd-label">Observações</label><textarea class="cd-input" name="observacoes" rows="2">{{ old('observacoes', $item->observacoes) }}</textarea></div>
                </div>
            </details>

            <div class="flex gap-2">
                <button class="cd-btn" type="submit">Salvar</button>
                <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.estoque.index') }}">Cancelar</a>
            </div>
        </form>

        @if ($editando)
            <form method="POST" action="{{ route('cardapio.estoque.destroy', $item) }}" onsubmit="return confirm('Remover este item?')">
                @csrf @method('DELETE')
                <button class="cd-btn cd-btn-perigo" type="submit">Remover item</button>
            </form>
        @endif
    @endif
</x-Cardapio::layout>
