@extends('VocabularioControlado::layout')

@section('content')

<h2>Listas de Valores</h2>

<div class="mdl-tabs mdl-js-tabs">
    <div class="mdl-tabs__tab-bar">
        @foreach ($nomeListas as $i => $lista)
        <a href="#tab-{{ $i }}" class="mdl-tabs__tab {{ $i === 0 ? 'is-active' : '' }}">
            {{ $lista->value_pairs_name }}
        </a>
        @endforeach
    </div>

    @foreach ($nomeListas as $i => $lista)
    <div class="mdl-tabs__panel {{ $i === 0 ? 'is-active' : '' }}" id="tab-{{ $i }}">
        <table class="mdl-data-table mdl-js-data-table" style="width:100%;margin-top:8px;">
            <thead>
                <tr>
                    <th class="mdl-data-table__cell--non-numeric">Valor armazenado</th>
                    <th class="mdl-data-table__cell--non-numeric">Valor exibido</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($listaValores->get($lista->value_pairs_name, collect()) as $v)
                <tr>
                    <td class="mdl-data-table__cell--non-numeric">{{ $v->stored_value }}</td>
                    <td class="mdl-data-table__cell--non-numeric">{{ $v->displayed_value }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</div>

@endsection
