@extends('VocabularioControlado::layout')

@section('content')

<h1 class="mdl-typography--text-center">Novo vocabulário</h1>

<form action="{{ route('vocabulario-controlado.admin.store') }}" method="post">
    @csrf

    <div style="margin:auto;width:fit-content;max-width:90vw;">

        @error('palavra')
        <p style="color:red;">{{ $message }}</p>
        @enderror

        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
            <input class="mdl-textfield__input" type="text" id="palavra" name="palavra"
                   value="{{ old('palavra') }}" required>
            <label class="mdl-textfield__label" for="palavra">Palavra</label>
        </div>
        <br><br>

        <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">
            Salvar
        </button>
        <a href="{{ route('vocabulario-controlado.admin.index') }}"
           class="mdl-button mdl-js-button">
            Cancelar
        </a>
    </div>
</form>

@endsection
