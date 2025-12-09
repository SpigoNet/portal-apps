<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.home') }}" class="text-indigo-600 hover:underline">Dashboard</a> &rsaquo;
            Boletim: {{ $materia->nome }} ({{ $semestreAtual }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Card de Nota Final (Destaque) --}}
            @php
                // Verifica aprovação considerando a regra de 60% (6.0/10) ajustada ao Peso Total
                $aprovado = $notaFinal >= 6.0 * ($pesoTotal / 10);
                $finalCorClass = $aprovado ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50';
            @endphp
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8 border-l-8 {{ $finalCorClass }}">
                <div class="p-6 bg-white border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-700">Sua Nota Final Ponderada</h3>
                        <p class="text-gray-500">{{ $materia->nome }}</p>
                    </div>
                    <div class="text-center">
                        <span class="block text-sm text-gray-400 uppercase tracking-wide">Total Geral</span>
                        <span class="text-5xl font-extrabold {{ $aprovado ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($notaFinal, 2) }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">
                            (Baseado em Peso Total: {{ number_format($pesoTotal, 1) }})
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                @forelse($gruposNome as $pesoId => $nome)
                    @php
                        $dadosGrupo = $notasPorGrupo[$pesoId];
                        $media = $dadosGrupo['mediaGrupo'];
                        $notaPonderada = $dadosGrupo['notaPonderada'];
                        $totalNotas = $dadosGrupo['totalNotas'];

                        // Lógica de cor (Vermelho se a média 0-10 for menor que 6.0)
                        $mediaCorClass = $media >= 6.0 ? 'text-indigo-700' : 'text-red-600';
                        $cardCorClass = $media >= 6.0 ? 'border-green-200' : 'border-red-200';
                    @endphp

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border {{ $cardCorClass }}">
                        <div class="p-6">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-bold text-xl text-gray-800">{{ $nome }}</h4>
                                    <p class="text-sm text-gray-500">
                                        {{ $totalNotas }} Atividade(s) | Peso: {{ $pesos->where('id', $pesoId)->first()->valor }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="block text-sm text-gray-400 uppercase tracking-wide">Média (0-10)</span>
                                    <span class="text-3xl font-extrabold {{ $mediaCorClass }}">
                                        {{ number_format($media, 1) }}
                                    </span>
                                </div>
                            </div>

                            <hr class="my-4 border-gray-100">

                            <div class="flex justify-between items-center">
                                <p class="text-sm font-medium text-gray-700">Contribuição para Nota Final:</p>
                                <span class="text-xl font-bold {{ $mediaCorClass }}">
                                    {{ number_format($notaPonderada, 2) }}
                                </span>
                            </div>
                            @if($totalNotas == 0)
                                <p class="text-sm text-gray-400 italic mt-2">Nenhuma nota corrigida neste grupo ainda.</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200">
                        <p class="text-yellow-700">Nenhum grupo de notas (Pesos) definido para esta matéria.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-ANT::layout>
