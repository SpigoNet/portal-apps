<x-ANT::layout>
    <x-slot name="header">
        {{ __('Área de Notas e Trabalhos') }} - {{ $semestreAtual }}
        <br>
        Olá, {{ explode(' ', $aluno->nome)[0] }}!
        - RA: {{ $aluno->ra }}
        - Disciplinas Matriculadas: {{ $materias->count() }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($materias->isEmpty())
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Você não está matriculado em nenhuma matéria neste semestre ({{ $semestreAtual }}).
                                Entre em contato com a secretaria se isso for um erro.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6">
                @foreach($materias as $materia)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600 mr-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <h4 class="font-bold text-lg text-gray-800">{{ $materia->nome }}</h4>
                            </div>
                            <span
                                class="text-xs font-mono text-gray-500 bg-white px-3 py-1 rounded-full border border-gray-200 shadow-sm">{{ $materia->nome_curto }}</span>
                        </div>

                        <div class="p-6">
                            @if($materia->trabalhos->isEmpty())
                                <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                                    <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="text-sm italic">Nenhum trabalho ou prova agendado por enquanto.</p>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th
                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Atividade
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Tipo
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Prazo
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ação
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($materia->trabalhos as $trabalho)
                                                @php
                                                    // Lógica de Status
                                                    $entrega = $trabalho->entregas->first();
                                                    $isProva = !empty($trabalho->prova);
                                                    $agora = now();
                                                    $prazo = \Carbon\Carbon::parse($trabalho->prazo)->endOfDay();
                                                    $atrasado = !$entrega && $agora->gt($prazo);
                                                @endphp
                                                <tr class="{{ $atrasado ? 'bg-red-50' : 'hover:bg-gray-50 transition-colors' }}">

                                                    <td class="px-3 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="mr-3">
                                                                @if($isProva)
                                                                    <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900">{{ $trabalho->nome }}
                                                                </div>
                                                                <div class="text-xs text-gray-500 truncate w-64"
                                                                    title="{{ $trabalho->descricao }}">
                                                                    {{ $trabalho->descricao }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="px-3 py-4 whitespace-nowrap">
                                                        @if($isProva)
                                                            <span
                                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                                Prova
                                                            </span>
                                                        @else
                                                            <span
                                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                                Trabalho
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-3 py-4 whitespace-nowrap">
                                                        <div
                                                            class="text-sm {{ $atrasado ? 'text-red-600 font-bold' : 'text-gray-900' }}">
                                                            {{ $prazo->format('d/m/Y') }}
                                                        </div>
                                                        <div class="text-xs text-gray-400">até as 23:59</div>
                                                    </td>

                                                    <td class="px-3 py-4 whitespace-nowrap text-center">
                                                        @if($entrega)
                                                            @if(!is_null($entrega->nota))
                                                                <span
                                                                    class="px-2 inline-flex text-xs leading-5 font-bold rounded-full bg-green-100 text-green-800 border border-green-200">
                                                                    Nota: {{ number_format($entrega->nota, 1) }}
                                                                </span>
                                                            @else
                                                                <span
                                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                                    Entregue (Aguardando)
                                                                </span>
                                                            @endif
                                                        @elseif($atrasado)
                                                            <span
                                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                Pendente / Atrasado
                                                            </span>
                                                        @else
                                                            <span
                                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                                Aberto
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-3 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        @if($isProva)
                                                            <a href="{{ route('ant.prova.resultado', $trabalho->id) }}"
                                                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                                                Ver Prova
                                                            </a>
                                                        @else
                                                            @if($entrega)
                                                                <a href="{{ route('ant.trabalhos.show', $trabalho->id) }}"
                                                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                                    Detalhes
                                                                </a>
                                                            @elseif($atrasado)
                                                                <a href="{{ route('ant.trabalhos.show', $trabalho->id) }}"
                                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                                    Entregar
                                                                </a>
                                                            @else
                                                                <a href="{{ route('ant.trabalhos.show', $trabalho->id) }}"
                                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                                    Entregar
                                                                </a>
                                                            @endif
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            {{-- NOVO LINK PARA BOLETIM --}}
                            <div class="mt-4 pt-3 border-t border-gray-100 flex justify-end">
                                <a href="{{ route('ant.aluno.boletim', $materia->id) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-bold flex items-center">
                                    <span class="material-icons text-sm mr-1">bar_chart</span>
                                    Ver Notas Detalhadas (Boletim)
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-ANT::layout>