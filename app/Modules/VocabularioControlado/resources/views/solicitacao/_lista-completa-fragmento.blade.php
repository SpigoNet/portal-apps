<table>
    <tbody id="fragment-rows">
    @foreach ($listaTermos as $t)
    <tr data-lista-item="1" class="hover:bg-gray-50">
        <td class="px-4 py-2 font-medium">{{ $t->palavra }}</td>
        <td class="px-4 py-2">
            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800">{{ $t->status }}</span>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

@php
    $palavraLista = trim((string) request()->query('palavra_lista', ''));

    $proximaUrl = $listaTermos->hasMorePages()
        ? route('vocabulario-controlado.solicitacao.lista-fragmento', array_merge(
            request()->only(['mail', 'nome']),
            ['palavra_lista' => $palavraLista, 'pagina_lista' => $listaTermos->currentPage() + 1]
        ))
        : '';
@endphp

<div data-next-url="{{ $proximaUrl }}"></div>
