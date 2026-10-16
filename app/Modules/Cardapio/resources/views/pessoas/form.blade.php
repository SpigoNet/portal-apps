@php($editando = $pessoa->exists)
@php($sou = $editando && $pessoa->user_id === auth()->id())
<x-Cardapio::layout :titulo="$editando ? 'Editar pessoa' : 'Nova pessoa'">
    <h1 class="text-2xl font-extrabold">{{ $editando ? 'Editar '.$pessoa->nome : 'Nova pessoa' }}</h1>

    <form method="POST"
          action="{{ $editando ? route('cardapio.pessoas.update', $pessoa) : route('cardapio.pessoas.store') }}"
          class="cd-card space-y-4">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div>
            <label class="cd-label" for="nome">Nome</label>
            <input class="cd-input" id="nome" name="nome" value="{{ old('nome', $pessoa->nome) }}" required maxlength="120">
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="cd-label" for="tipo">Tipo</label>
                <select class="cd-input" id="tipo" name="tipo">
                    <option value="adulto" @selected(old('tipo', $pessoa->tipo) === 'adulto')>Adulto</option>
                    <option value="crianca" @selected(old('tipo', $pessoa->tipo) === 'crianca')>Criança</option>
                </select>
            </div>
            <div>
                <label class="cd-label" for="data_nascimento">Data de nascimento</label>
                <input class="cd-input" type="date" id="data_nascimento" name="data_nascimento"
                       value="{{ old('data_nascimento', $pessoa->data_nascimento?->format('Y-m-d')) }}">
            </div>
        </div>

        <div>
            <label class="cd-label" for="user_id">Login (opcional)</label>
            @if ($sou)
                <input type="hidden" name="user_id" value="{{ $pessoa->user_id }}">
                <div class="text-sm">Este é o seu usuário e não pode ser desvinculado.</div>
            @else
                <select class="cd-input" id="user_id" name="user_id">
                    <option value="">Sem login (ex.: criança, empregada)</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected((string) old('user_id', $pessoa->user_id) === (string) $usuario->id)>
                            {{ $usuario->name }} ({{ $usuario->email }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs mt-1">A pessoa participa do cardápio mesmo sem login. Com login, ela também acessa o módulo.</p>
            @endif
        </div>

        <div>
            <label class="cd-label" for="observacoes">Observações</label>
            <textarea class="cd-input" id="observacoes" name="observacoes" rows="3">{{ old('observacoes', $pessoa->observacoes) }}</textarea>
        </div>

        @unless ($sou)
            <label class="flex items-center gap-2 font-semibold">
                <input type="hidden" name="ativo" value="0">
                <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $pessoa->ativo))>
                Ativa (entra nos cálculos de refeições)
            </label>
        @endunless

        <div class="flex gap-2">
            <button class="cd-btn" type="submit">Salvar</button>
            <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.pessoas.index') }}">Cancelar</a>
        </div>
    </form>

    @if ($editando && ! $sou)
        <form method="POST" action="{{ route('cardapio.pessoas.destroy', $pessoa) }}"
              onsubmit="return confirm('Remover {{ $pessoa->nome }}?')">
            @csrf
            @method('DELETE')
            <button class="cd-btn cd-btn-perigo" type="submit">Remover pessoa</button>
        </form>
    @endif
</x-Cardapio::layout>
