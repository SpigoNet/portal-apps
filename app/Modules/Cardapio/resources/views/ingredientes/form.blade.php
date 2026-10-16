@php($editando = $ingrediente->exists)
<x-Cardapio::layout :titulo="$editando ? 'Editar ingrediente' : 'Novo ingrediente'">
    <h1 class="text-2xl font-extrabold">{{ $editando ? 'Editar '.$ingrediente->nome : 'Novo ingrediente' }}</h1>

    <form method="POST" class="cd-card space-y-4"
          action="{{ $editando ? route('cardapio.ingredientes.update', $ingrediente) : route('cardapio.ingredientes.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div>
            <label class="cd-label" for="nome">Nome</label>
            <input class="cd-input" id="nome" name="nome" value="{{ old('nome', $ingrediente->nome) }}" required maxlength="120">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="cd-label" for="categoria_id">Categoria</label>
                <select class="cd-input" id="categoria_id" name="categoria_id">
                    <option value="">Sem categoria</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((string) old('categoria_id', $ingrediente->categoria_id) === (string) $categoria->id)>{{ $categoria->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="cd-label" for="unidade_base">Unidade base</label>
                <select class="cd-input" id="unidade_base" name="unidade_base">
                    <option value="g" @selected(old('unidade_base', $ingrediente->unidade_base) === 'g')>Peso (g / kg)</option>
                    <option value="ml" @selected(old('unidade_base', $ingrediente->unidade_base) === 'ml')>Volume (ml / L)</option>
                    <option value="un" @selected(old('unidade_base', $ingrediente->unidade_base) === 'un')>Unidades (un / dúzia / pacote…)</option>
                </select>
            </div>
        </div>

        @if ($editando && $outros->isNotEmpty())
            <details class="text-sm" @if (count($substitutos)) open @endif>
                <summary class="font-bold cursor-pointer">Pode ser substituído por…</summary>
                <p class="my-2">Usado quando alguém tem restrição ao ingrediente (ex.: creme de leite → creme de leite sem lactose).</p>
                <div class="grid sm:grid-cols-2 gap-1">
                    @foreach ($outros as $outro)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="substitutos[]" value="{{ $outro->id }}" @checked(in_array($outro->id, old('substitutos', $substitutos)))>
                            {{ $outro->nome }}
                        </label>
                    @endforeach
                </div>
            </details>
        @endif

        <div class="flex gap-2">
            <button class="cd-btn" type="submit">Salvar</button>
            <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.ingredientes.index') }}">Cancelar</a>
        </div>
    </form>

    @if ($editando)
        <form method="POST" action="{{ route('cardapio.ingredientes.destroy', $ingrediente) }}"
              onsubmit="return confirm('Remover este ingrediente?')">
            @csrf
            @method('DELETE')
            <button class="cd-btn cd-btn-perigo" type="submit">Remover ingrediente</button>
        </form>
    @endif
</x-Cardapio::layout>
