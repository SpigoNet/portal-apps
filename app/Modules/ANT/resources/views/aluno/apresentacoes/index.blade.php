<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.home') }}" class="text-gray-500 hover:text-gray-900">Minhas Aulas</a>
            <span class="text-gray-400 mx-2">/</span>
            Minhas Apresentações
            @if($materiaFiltro)
                <span class="text-gray-500 font-normal text-lg">· {{ $materiaFiltro->nome }}</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if($materiaFiltro)
                <a href="{{ route('ant.home') }}" class="text-sm text-indigo-600 hover:text-indigo-900 mb-4 inline-flex items-center">
                    ← Voltar para Minhas Aulas
                </a>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @forelse($apresentadores as $apresentador)
                        @php
                            $ag = $apresentador->agendamento;
                            $apr = $ag->apresentacao;
                        @endphp
                        <div class="border-b border-gray-100 last:border-0 py-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="font-bold text-gray-900">{{ $apr->nome }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ $apr->materia->nome }}
                                    · {{ \Carbon\Carbon::parse($ag->data)->format('d/m/Y') }}
                                    · Tema: {{ $ag->tema }}
                                </div>
                                <div class="text-xs mt-1">
                                    @if($apresentador->entrega)
                                        <span class="text-green-600">✓ Entrega enviada</span>
                                    @else
                                        <span class="text-orange-500">Pendente: enviar apresentação</span>
                                    @endif
                                    @if($apresentador->nota !== null)
                                        <span class="text-gray-500 ml-2">Nota: <strong>{{ number_format($apresentador->nota, 1) }}</strong></span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('ant.apresentacoes.aluno.show', $ag->id) }}"
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold whitespace-nowrap">
                                {{ $apresentador->entrega ? 'Ver / Editar' : 'Enviar' }} →
                            </a>
                        </div>
                    @empty
                        <p class="text-gray-500 italic text-center py-8">
                            Você ainda não foi escalado para nenhuma apresentação.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-ANT::layout>
