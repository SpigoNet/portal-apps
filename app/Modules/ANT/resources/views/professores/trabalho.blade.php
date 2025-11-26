<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <a href="{{ route('ant.professor.index') }}" class="text-gray-500 hover:text-gray-900">Dashboard</a>
                <span class="text-gray-400 mx-2">/</span>
                {{ $trabalho->nome }}
            </h2>
            <div class="text-sm text-gray-500">
                {{ $trabalho->materia->nome }}
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded shadow border-l-4 border-gray-500">
                    <span class="block text-gray-500 text-xs uppercase">Total Alunos</span>
                    <span class="text-2xl font-bold">{{ $totalAlunos }}</span>
                </div>
                <div class="bg-white p-4 rounded shadow border-l-4 border-blue-500">
                    <span class="block text-gray-500 text-xs uppercase">Entregues</span>
                    <span class="text-2xl font-bold text-blue-600">{{ $entregues }}</span>
                </div>
                <div class="bg-white p-4 rounded shadow border-l-4 border-yellow-500">
                    <span class="block text-gray-500 text-xs uppercase">Pendentes de Correção</span>
                    <span class="text-2xl font-bold text-yellow-600">{{ $entregues - $corrigidos }}</span>
                </div>
                <div class="bg-white p-4 rounded shadow border-l-4 border-green-500">
                    <span class="block text-gray-500 text-xs uppercase">Corrigidos</span>
                    <span class="text-2xl font-bold text-green-600">{{ $corrigidos }}</span>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno / RA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Envio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Entrega</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nota</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($alunos as $aluno)
                            @php
                                $entrega = $aluno->entregas->first();
                                $status = 'pendente';
                                $statusClass = 'bg-gray-100 text-gray-800';
                                $statusText = 'Não Entregue';

                                // Cálculo de Atraso
                                $prazo = \Carbon\Carbon::parse($trabalho->prazo)->endOfDay();

                                if ($entrega) {
                                    if ($entrega->data_entrega->gt($prazo)) {
                                        $status = 'atrasado';
                                        $statusClass = 'bg-red-100 text-red-800';
                                        $statusText = 'Entregue com Atraso';
                                    } else {
                                        $status = 'entregue';
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        $statusText = 'Entregue';
                                    }

                                    if ($entrega->nota !== null) {
                                        $status = 'corrigido';
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $statusText = 'Corrigido';
                                    }
                                } else {
                                    if (now()->gt($prazo)) {
                                        $statusClass = 'bg-red-50 text-red-400';
                                        $statusText = 'Não Entregue (Prazo Esgotado)';
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">{{ $aluno->nome }}</div>
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $aluno->ra }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                            {{ $statusText }}
                                        </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $entrega ? $entrega->data_entrega->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold">
                                    @if($entrega && $entrega->nota !== null)
                                        <span class="{{ $entrega->nota >= 6 ? 'text-blue-600' : 'text-red-600' }}">
                                                {{ number_format($entrega->nota, 1) }}
                                            </span>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($entrega)
                                        <a href="{{ route('ant.correcao.edit', $entrega->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100">
                                            {{ $entrega->nota !== null ? 'Alterar Nota' : 'Corrigir' }}
                                        </a>
                                    @else
                                        <span class="text-gray-300 cursor-not-allowed">Sem arquivo</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
