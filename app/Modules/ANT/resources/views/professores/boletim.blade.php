<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.professor.index') }}" class="text-gray-500 hover:text-gray-900">Dashboard</a>
            <span class="text-gray-400 mx-2">/</span>
            Boletim de Notas - {{ $materia->nome }} ({{ $semestreAtual }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                <p class="text-sm text-blue-800">
                    O cálculo da **Nota Final** é feito pela **média ponderada** das notas obtidas pelo aluno em cada grupo (Peso).
                    O Peso Total Distribuído é de: <strong class="text-lg">{{ number_format($pesoTotal, 1) }}</strong>.
                </p>
                @if($pesoTotal != 10 && $pesoTotal != 100)
                    <p class="text-xs text-red-600 mt-1">
                        ⚠️ Atenção: O Peso Total Distribuído não é 10 ou 100. A nota final será proporcional a este total.
                    </p>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 w-64">
                                Aluno / RA
                            </th>
                            @foreach($gruposNome as $pesoId => $nome)
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l border-gray-200">
                                    {{ $nome }}
                                </th>
                            @endforeach
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-indigo-50 border-l-4 border-indigo-200">
                                Nota Final
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($dadosBoletim as $dados)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white z-10">
                                    <div class="font-bold">{{ $dados['aluno']->nome }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $dados['ra'] }}</div>
                                </td>

                                @foreach($gruposNome as $pesoId => $nome)
                                    @php
                                        $notasGrupo = $dados['notasGrupos'][$pesoId];
                                        $media = $notasGrupo['mediaGrupo'] ?? 0;
                                        $notaPonderada = $notasGrupo['notaPonderada'] ?? 0;
                                        // LÓGICA DE COR: Vermelho se a média 0-10 for menor que 6.0
                                        $mediaCorClass = $media >= 6.0 ? 'text-indigo-700' : 'text-red-600';
                                    @endphp
                                    {{-- Aplicação da Lógica de Cor --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-center border-l border-gray-100">
                                        @if($notasGrupo['totalNotas'] > 0)
                                            {{-- Média 0-10 com destaque (COR APLICADA AQUI) --}}
                                            <div class="text-xl font-extrabold {{ $mediaCorClass }}" title="Média Aritmética das notas (0-10) dos trabalhos deste grupo">
                                                {{ number_format($media, 1) }}
                                            </div>
                                            {{-- Valor ponderado menor, abaixo (COR APLICADA AQUI) --}}
                                            <div class="text-xs text-gray-500 mt-0.5" title="Valor que este grupo adiciona à Nota Final">
                                                (<span class="{{ $mediaCorClass }} font-bold">{{ number_format($notaPonderada, 2) }}</span> Ponderado)
                                            </div>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-6 py-4 whitespace-nowrap text-center text-lg font-extrabold border-l-4 border-indigo-200 bg-indigo-50">
                                    @php
                                        $nota = $dados['notaFinal'];
                                        // A aprovação é baseada no peso total, mantendo a regra de 60%
                                        $aprovado = $nota >= 6.0 * ($pesoTotal / 10);
                                    @endphp
                                    <span class="{{ $aprovado ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($nota, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($gruposNome) + 2 }}" class="px-6 py-10 text-center text-gray-500 italic">
                                    Nenhum aluno matriculado ou notas lançadas para esta matéria.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-ANT::layout>
