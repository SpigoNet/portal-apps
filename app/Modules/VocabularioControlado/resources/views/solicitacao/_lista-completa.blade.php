{{-- Partial: lista pública de termos aprovados/disponíveis --}}
@php
    $listaTermos = \App\Modules\VocabularioControlado\Models\Vocabulario
        ::whereIn('status', ['Disponível', 'Aprovado'])
        ->orderByRaw('TRIM(palavra)')
        ->get();
@endphp

<form action="{{ route('vocabulario-controlado.buscar') }}" method="post" target="ifr-lista">
    @csrf
    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
        <input class="mdl-textfield__input" type="text" id="srch" name="palavra">
        <label class="mdl-textfield__label" for="srch">Pesquisar na lista</label>
    </div>
    <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored">Pesquisar</button>
</form>

<table class="mdl-data-table mdl-js-data-table" style="width:100%;margin-top:16px;">
    <thead>
        <tr>
            <th class="mdl-data-table__cell--non-numeric">Termo</th>
            <th class="mdl-data-table__cell--non-numeric">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($listaTermos as $t)
        <tr>
            <td class="mdl-data-table__cell--non-numeric">{{ $t->palavra }}</td>
            <td class="mdl-data-table__cell--non-numeric">{{ $t->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
