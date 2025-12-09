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
                            <h4 class="font-bold text-lg text-gray-800">{{ $materia->nome }}</h4>
                            <span
                                class="text-xs font-mono text-gray-400 bg-white px-2 py-1 rounded border">{{ $materia->nome_curto }}</span>
                        </div>

                        <div class="p-6">
                            @if($materia->trabalhos->isEmpty())
                                <p class="text-gray-400 text-sm italic">Nenhum trabalho ou prova agendado por
                                    enquanto.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Atividade
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Tipo
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Prazo
                                            </th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                                            <tr class="{{ $atrasado ? 'bg-red-50' : '' }}">

                                                <td class="px-3 py-4 whitespace-nowrap">
                                                    <div
                                                        class="text-sm font-medium text-gray-900">{{ $trabalho->nome }}</div>
                                                    <div class="text-xs text-gray-500 truncate w-64"
                                                         title="{{ $trabalho->descricao }}">
                                                        {{ $trabalho->descricao }}
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
                                                        @php
                                                            // Verifica se o aluno já tem alguma resposta registrada para esta prova
                                                            $temResposta = false;
                                                            if ($trabalho->prova) {
                                                                // Como não carregamos as respostas na query do Home para não pesar,
                                                                // podemos verificar se existe 'nota' ou se status está entregue na tabela de entregas (se houver vínculo)
                                                                // OU, para simplificar "por enquanto", assumimos que o link leva para ver o resultado/tentar
                                                                // Idealmente: $temResposta = \App\Modules\ANT\Models\AntProvaResposta::where('prova_id', $trabalho->prova->id)->where('aluno_ra', $aluno->ra)->exists();
                                                                // Mas vamos usar o link direto conforme pedido.
                                                            }
                                                        @endphp

                                                        <a href="{{ route('ant.prova.resultado', $trabalho->id) }}"
                                                           class="text-purple-600 hover:text-purple-900 font-bold hover:underline">
                                                            Ver Prova / Resultado
                                                        </a>

                                                    @else
                                                        <a href="{{ route('ant.trabalhos.show', $trabalho->id) }}"
                                                           class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline">
                                                            @if($entrega)
                                                                Ver Detalhes
                                                            @elseif($atrasado)
                                                                Entregar com Atraso
                                                            @else
                                                                Entregar
                                                            @endif
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-ANT::layout>
