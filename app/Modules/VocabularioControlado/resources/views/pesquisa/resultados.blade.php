@php $semMenu = true; @endphp
@extends('VocabularioControlado::layout')

@section('content')
<style>
    span.destaque { background-color: yellow; }
</style>

<table class="mdl-data-table mdl-js-data-table" style="width:100%">
    <thead>
        <tr>
            <th class="mdl-data-table__cell--non-numeric">Palavra</th>
            <th class="mdl-data-table__cell--non-numeric">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($resultados as $item)
        <tr>
            <td class="mdl-data-table__cell--non-numeric">
                {!! str_ireplace($termo, '<span class="destaque">'.strtoupper($termo).'</span>', e($item->palavra)) !!}
            </td>
            <td class="mdl-data-table__cell--non-numeric" title="{{ $item->motivoReprova }}">
                {{ $item->status }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="2" class="mdl-data-table__cell--non-numeric">Nenhum termo encontrado.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
