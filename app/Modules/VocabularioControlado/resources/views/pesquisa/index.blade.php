@extends('VocabularioControlado::layout')
@section('titulo', 'Vocabulário Controlado')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-blue-800 mb-2">Vocabulário Controlado</h1>
    <p class="text-gray-600 text-sm max-w-2xl">
        Macroestrutura de linguagem artificial em ordem alfabética, para controle da terminologia
        adotada no cadastramento de novos itens documentais.
    </p>
</div>

{{-- Barra de pesquisa --}}
<form action="{{ route('vocabulario-controlado.index') }}" method="get" class="flex gap-2 mb-6">
    <input
        type="text"
        name="palavra"
        value="{{ $termo }}"
        placeholder="Pesquisar termo..."
        class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        autofocus
    >
    <button type="submit"
            class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-5 py-2 rounded-lg transition">
        Pesquisar
    </button>
    @if($termo)
    <a href="{{ route('vocabulario-controlado.index') }}"
       class="border border-gray-300 hover:bg-gray-100 text-gray-700 text-sm px-4 py-2 rounded-lg transition">
        Limpar
    </a>
    @endif
</form>

{{-- Resultados da busca --}}
@if($resultados !== null)
    <div class="mb-4 text-sm text-gray-500">
        {{ $resultados->count() }} resultado(s) para "<strong>{{ $termo }}</strong>"
    </div>

    @if($resultados->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800 text-sm">
            Nenhum termo encontrado para "{{ $termo }}".
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Termo</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($resultados as $item)
                    <tr class="hover:bg-blue-50 transition">
                        <td class="px-4 py-3 font-medium">
                            {!! str_ireplace(
                                e($termo),
                                '<mark class="bg-yellow-200 rounded px-0.5">'.e(strtoupper($termo)).'</mark>',
                                e($item->palavra)
                            ) !!}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $cores = [
                                    'Disponível'     => 'bg-green-100 text-green-800',
                                    'Aprovado'       => 'bg-blue-100 text-blue-800',
                                    'Solicitado'     => 'bg-yellow-100 text-yellow-800',
                                    'Não Autorizado' => 'bg-red-100 text-red-800',
                                ];
                                $cor = $cores[$item->status] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $cor }}">
                                {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@else
    {{-- Estado inicial: instrução + link para PDF --}}
    <div class="text-center py-16 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
        <p class="text-sm">Digite um termo acima para pesquisar.</p>
        <a href="{{ route('vocabulario-controlado.pdf') }}"
           target="_blank"
           class="mt-6 inline-block text-sm text-blue-600 hover:underline">
            ↓ Baixar / imprimir vocabulário completo em PDF
        </a>
    </div>
@endif

@endsection
