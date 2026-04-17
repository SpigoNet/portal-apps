@extends('VocabularioControlado::layout')

@section('content')

@if (session('success'))
<div style="padding:12px;background:#4caf50;color:#fff;margin-bottom:16px;border-radius:4px;">
    {{ session('success') }}
</div>
@endif

<div style="margin-bottom:16px;">
    <a href="{{ route('vocabulario-controlado.admin.criar') }}"
       class="mdl-button mdl-js-button mdl-button--fab mdl-button--colored"
       style="position:fixed;bottom:16px;right:16px;">
        <i class="material-icons">add</i>
    </a>
</div>

<table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp" style="width:100%;">
    <thead>
        <tr>
            <th class="mdl-data-table__cell--non-numeric">Palavra</th>
            <th class="mdl-data-table__cell--non-numeric">Status</th>
            <th class="mdl-data-table__cell--non-numeric">Ação</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($termos as $t)
        <tr>
            <td class="mdl-data-table__cell--non-numeric">{{ $t->palavra }}</td>
            <td class="mdl-data-table__cell--non-numeric">{{ $t->status }}</td>
            <td class="mdl-data-table__cell--non-numeric">
                <form method="post"
                      action="{{ route('vocabulario-controlado.admin.destroy', $t->id) }}"
                      onsubmit="return confirm('Excluir o termo \'{{ addslashes($t->palavra) }}\'?')"
                      style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button class="mdl-button mdl-button--icon">
                        <i class="material-icons">delete</i>
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="mdl-data-table__cell--non-numeric">Nenhum termo cadastrado.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@endsection
