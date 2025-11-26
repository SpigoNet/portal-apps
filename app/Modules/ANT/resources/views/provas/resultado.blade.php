<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.home') }}" class="text-indigo-600 hover:underline">Dashboard</a> &rsaquo;
            Resultado: {{ $trabalho->nome }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-8 border-indigo-500">
                <div class="p-6 bg-white border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-700">Resumo do Desempenho</h3>
                        <p class="text-gray-500">Aluno: {{ $aluno->nome }} ({{ $aluno->ra }})</p>
                    </div>
                    <div class="text-center">
                        <span class="block text-sm text-gray-400 uppercase tracking-wide">Sua Nota</span>
                        <span class="text-4xl font-extrabold text-indigo-600">{{ number_format($notaTotal, 1) }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                @foreach($prova->questoes as $questao)
                    @php
                        $respostaAluno = $respostas->get($questao->id);
                        $acertou = false;
                        $respostaTexto = null;

                        // Verifica se existe resposta
                        if ($respostaAluno) {
                            if ($questao->multipla_escolha) {
                                // Se for múltipla escolha, 'resposta' guarda o ID da alternativa
                                $acertou = $questao->alternativas->where('id', $respostaAluno->resposta)->first()->correta ?? false;
                            } else {
                                // Se for dissertativa/SQL, verifica se a pontuação é maior que 0 (lógica simples)
                                $acertou = $respostaAluno->pontuacao > 0;
                            }
                        }
                    @endphp

                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg border {{ $acertou ? 'border-green-200' : 'border-red-200' }}">

                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                            <span
                                class="text-xs font-bold text-gray-400 uppercase">Questão {{ $loop->iteration }}</span>
                            <div class="mt-2 text-gray-900 font-medium">
                                {!! nl2br(e($questao->enunciado)) !!}
                            </div>
                        </div>

                        <div class="p-6">
                            @if($questao->multipla_escolha)
                                <ul class="space-y-4"> @foreach($questao->alternativas as $alt)
                                        @php
                                            $foiSelecionada = $respostaAluno && $respostaAluno->resposta == $alt->id;

                                            // Estilos da CAIXA PRINCIPAL (Onde está o texto da alternativa)
                                            $classe = "p-3 rounded-t border-t border-l border-r "; // Borda arredondada só em cima se tiver explicação
                                            if (empty($alt->explicacao)) {
                                                $classe = "p-3 rounded border "; // Borda completa se não tiver explicação
                                            }

                                            if ($foiSelecionada) {
                                                $classe .= $alt->correta ? "bg-green-100 border-green-500 text-green-800" : "bg-red-100 border-red-500 text-red-800";
                                            } elseif ($alt->correta) {
                                                $classe .= "bg-green-50 border-green-200 text-green-700 opacity-75";
                                            } else {
                                                $classe .= "bg-white border-gray-200 text-gray-500";
                                            }
                                        @endphp

                                        <li>
                                            <div class="{{ $classe }} flex items-center relative">
                                                @if($foiSelecionada)
                                                    <span
                                                        class="material-icons mr-2 text-sm">{{ $alt->correta ? 'check_circle' : 'cancel' }}</span>
                                                @endif

                                                <span class="flex-1">{{ $alt->texto }}</span>

                                                @if($foiSelecionada)
                                                    <span
                                                        class="ml-2 text-xs font-bold uppercase">{{ $alt->correta ? 'Sua Resposta (Correta)' : 'Sua Resposta (Incorreta)' }}</span>
                                                @endif
                                            </div>

                                            @if(!empty($alt->explicacao))
                                                <div
                                                    class="p-3 bg-gray-50 border-b border-l border-r border-gray-200 rounded-b text-sm text-gray-600 flex items-start">
                                                    <span
                                                        class="material-icons text-xs mr-1 text-gray-400 mt-0.5">info</span>
                                                    <span>{!! nl2br(e($alt->explicacao)) !!}</span>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- AQUI ESTÁ A MUDANÇA: Exibir a explicação se existir --}}
                                @if(!empty($questao->query_correta))
                                    <div
                                        class="mt-4 p-4 bg-indigo-50 border-l-4 border-indigo-400 text-indigo-800 rounded">
                                        <h4 class="font-bold text-sm mb-1 flex items-center">
                                            <span class="material-icons text-sm mr-1">info</span> Exemplo da
                                            Resposta:
                                        </h4>
                                        <p class="text-sm">{{ $questao->query_correta }}</p>
                                    </div>
                                @endif

                            @else
                                <div class="mb-4">
                                    <h4 class="text-sm font-bold text-gray-500 mb-1">Sua Resposta:</h4>
                                    <div class="p-3 bg-gray-100 rounded border border-gray-300 font-mono text-sm">
                                        {{ $respostaAluno->resposta ?? 'Sem resposta' }}
                                    </div>
                                </div>

                                @if(!empty($questao->query_correta) || !empty($questao->database_name))
                                    <div class="mt-2">
                                        <h4 class="text-sm font-bold text-gray-500 mb-1">Gabarito Esperado (SQL
                                            Correto):</h4>
                                        <div
                                            class="p-3 bg-green-50 rounded border border-green-200 font-mono text-sm text-green-800">
                                            {{ $questao->query_correta }}
                                        </div>
                                    </div>
                                @endif
                            @endif

                            <div class="mt-4 text-right border-t pt-4">
                                <span class="text-sm font-bold {{ $acertou ? 'text-green-600' : 'text-red-600' }}">
                                    Pontuação Obtida: {{ $respostaAluno->pontuacao ?? 0 }}
                                </span>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</x-app-layout>
