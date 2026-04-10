<x-app-layout>
    <x-slot name="header">
        @if($contrato->status !== 'ativo')
            <div
                class="mb-6 rounded-md p-4 {{ $contrato->status == 'cancelado' ? 'bg-red-50 border-l-4 border-red-500' : 'bg-gray-100 border-l-4 border-gray-500' }}">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 {{ $contrato->status == 'cancelado' ? 'text-red-400' : 'text-gray-400' }}"
                             viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium {{ $contrato->status == 'cancelado' ? 'text-red-800' : 'text-gray-800' }} uppercase">
                            Contrato {{ ucfirst($contrato->status) }}
                        </h3>
                        <div
                            class="mt-2 text-sm {{ $contrato->status == 'cancelado' ? 'text-red-700' : 'text-gray-700' }}">
                            <p>Este projeto está arquivado. As informações estão disponíveis apenas para consulta
                                (Leitura).</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @php
                $isLivre = $contrato->tipo === 'livre';
                $totalSeparado = $contrato->apontamentos->where('faturamento_status', 'separado')->count();
                $totalFaturado = $contrato->apontamentos->where('faturamento_status', 'faturado')->count();
            @endphp

            <div
                class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 {{ $isLivre ? 'border-slate-500' : ($contrato->saldo < 0 ? 'border-red-500' : 'border-blue-500') }}">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Cliente</p>
                        <p class="text-lg font-bold text-gray-800">{{ $contrato->cliente->nome }}</p>
                    </div>
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Total Consumido</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($contrato->horas_consumidas, 2, ',', '.') }}
                            h</p>
                    </div>
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Total Contratado</p>
                        <p class="text-2xl font-bold text-gray-700">{{ number_format($contrato->horas_contratadas, 2, ',', '.') }}
                            h</p>
                    </div>
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Valor Hora</p>
                        <p class="text-2xl font-bold text-emerald-700">R$ {{ number_format($contrato->valor_hora, 2, ',', '.') }}</p>
                    </div>
                    @if($isLivre)
                        <div class="px-4 py-2">
                            <p class="text-xs text-gray-400 uppercase font-bold">Faturamento</p>
                            <p class="text-sm font-bold text-slate-700">
                                {{ $totalSeparado }} separado(s) / {{ $totalFaturado }} faturado(s)
                            </p>
                        </div>
                    @else
                        <div class="px-4 py-2">
                            <p class="text-xs text-gray-400 uppercase font-bold">Saldo Disponível</p>
                            <p class="text-2xl font-bold {{ $contrato->saldo < 0 ? 'text-red-500' : 'text-green-500' }}">
                                {{ number_format($contrato->saldo, 2, ',', '.') }} h
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            @if($contrato->itens->count() > 0)
                <!-- Controle de Visualizações -->
                <div x-data="{ viewType: 'item' }" class="space-y-6">
                    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
                        <div class="flex flex-wrap gap-2 p-4 border-b border-gray-200 bg-gray-50">
                            <button @click="viewType = 'item'" :class="viewType === 'item' ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'" class="px-4 py-2 rounded-lg border-2 font-semibold transition">
                                📋 Por Item de Contrato
                            </button>
                            <button @click="viewType = 'month'" :class="viewType === 'month' ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'" class="px-4 py-2 rounded-lg border-2 font-semibold transition">
                                📅 Por Ano-Mês
                            </button>
                            <button @click="viewType = 'status'" :class="viewType === 'status' ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'" class="px-4 py-2 rounded-lg border-2 font-semibold transition">
                                💰 Por Status de Faturamento
                            </button>
                        </div>

                        <!-- View 1: Por Item de Contrato -->
                        <div x-show="viewType === 'item'" class="p-4">
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 px-1 border-b pb-2">Acompanhamento do Escopo</h3>

                    @foreach($contrato->itens as $item)
                        @php
                            $realizado = $item->horas_realizadas;
                            $estimado = $item->horas_estimadas;
                            $porcentagem = ($estimado > 0) ? ($realizado / $estimado) * 100 : 0;

                            $corBarra = 'bg-green-500';
                            if($porcentagem > 100) $corBarra = 'bg-red-500';
                            elseif($porcentagem > 85) $corBarra = 'bg-yellow-500';
                        @endphp

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">

                            <div class="p-6 bg-white relative">
                                <div class="flex flex-col md:flex-row justify-between md:items-center mb-2">
                                    <h4 class="font-bold text-gray-800 text-lg">{{ $item->titulo }}</h4>
                                    <div class="text-sm">
                                        <span
                                            class="font-bold {{ $realizado > $estimado ? 'text-red-600' : 'text-gray-700' }}">
                                            {{ number_format($realizado, 2, ',', '.') }}h
                                        </span>
                                        <span
                                            class="text-gray-400"> / {{ number_format($estimado, 2, ',', '.') }}h</span>
                                    </div>
                                </div>

                                <div class="w-full bg-gray-200 rounded-full h-3 mb-3">
                                    <div class="{{ $corBarra }} h-3 rounded-full"
                                         style="width: {{ min($porcentagem, 100) }}%"></div>
                                </div>
                                <p class="text-sm text-gray-600 mb-4">{{ $item->descricao }}</p>
                            </div>

                            <div class="bg-gray-50 border-t border-gray-200 p-4 sm:p-6">
                                <h5 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Apontamentos deste Item
                                </h5>

                                @if($item->apontamentos->count() > 0)
                                    <div class="bg-white rounded-md border border-gray-200 overflow-hidden">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100 text-gray-500">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-medium w-32">Data</th>
                                                <th class="px-4 py-2 text-left font-medium">Descrição da Tarefa</th>
                                                <th class="px-4 py-2 text-right font-medium w-24">Tempo</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                            @foreach($item->apontamentos as $log)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $log->data_realizacao->format('d/m/Y') }}</td>
                                                    <td class="px-4 py-2 text-gray-800">{{ $log->descricao }}</td>
                                                    <td class="px-4 py-2 text-right font-mono text-gray-600 font-bold">{{ number_format($log->horas, 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400 italic">Nenhuma hora lançada especificamente para
                                        este item.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                        </div>

                        <!-- View 2: Por Ano-Mês -->
                        <div x-show="viewType === 'month'" class="p-4">
                            @php
                                $apontamentosPorMes = $contrato->apontamentos
                                    ->groupBy(function($item) { return $item->data_realizacao->format('Y-m'); })
                                    ->sortKeys(SORT_DESC);
                            @endphp
                            
                            @if($apontamentosPorMes->count() > 0)
                                <div class="space-y-6">
                                    @foreach($apontamentosPorMes as $mesAno => $apontamentos)
                                        @php
                                            $mesFormatado = \Carbon\Carbon::createFromFormat('Y-m', $mesAno)->translatedFormat('F Y');
                                            $totalMes = $apontamentos->sum(function($a) { return $a->minutos_gastos / 60; });
                                            $valorMes = $totalMes * ($contrato->valor_hora ?? 0);
                                        @endphp
                                        <div class="bg-white rounded-lg shadow-sm border-2 border-blue-200 overflow-hidden">
                                            <div class="bg-blue-50 text-blue-900 px-6 py-4 font-semibold flex justify-between items-center">
                                                <div>
                                                    <span class="text-lg font-bold">{{ $mesFormatado }}</span>
                                                    <span class="text-sm opacity-75 ml-2">({{ $apontamentos->count() }} {{ $apontamentos->count() === 1 ? 'atividade' : 'atividades' }})</span>
                                                </div>
                                                <div class="flex gap-4 items-center">
                                                    <div class="font-bold">{{ number_format($totalMes, 2, ',', '.') }} h</div>
                                                    @if($contrato->valor_hora > 0)
                                                        <div class="font-bold text-emerald-700">R$ {{ number_format($valorMes, 2, ',', '.') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    <thead class="bg-gray-50 border-b border-gray-200">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left font-semibold text-gray-700 w-28">Data</th>
                                                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Descrição</th>
                                                        <th class="px-6 py-3 text-center font-semibold text-gray-700 w-32">Horário</th>
                                                        <th class="px-6 py-3 text-right font-semibold text-gray-700 w-24">Tempo</th>
                                                        @if($contrato->valor_hora > 0)
                                                            <th class="px-6 py-3 text-right font-semibold text-gray-700 w-28">Valor</th>
                                                        @endif
                                                        <th class="px-6 py-3 text-center font-semibold text-gray-700 w-32">Status</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                    @foreach($apontamentos as $apontamento)
                                                        @php
                                                            $statusAtual = $apontamento->faturamento_status ?? 'nao_separado';
                                                            $statusCor = match($statusAtual) {
                                                                'separado' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-300'],
                                                                'aprovado_cliente' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-300'],
                                                                'faturado' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-300'],
                                                                default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'border' => 'border-gray-300']
                                                            };
                                                            $statusLabel = str_replace('_', ' ', $statusAtual);
                                                        @endphp
                                                        <tr class="hover:bg-gray-50 transition">
                                                            <td class="px-6 py-3 text-gray-600 whitespace-nowrap font-medium text-sm">
                                                                {{ $apontamento->data_realizacao->format('d/m/Y') }}
                                                            </td>
                                                            <td class="px-6 py-3 text-gray-800 text-sm">
                                                                {{ $apontamento->descricao }}
                                                            </td>
                                                            <td class="px-6 py-3 text-center text-gray-600 whitespace-nowrap font-mono text-xs">
                                                                @if($apontamento->iniciado_em && $apontamento->finalizado_em)
                                                                    <span class="font-semibold">{{ $apontamento->iniciado_em->format('H:i') }}</span> a <span class="font-semibold">{{ $apontamento->finalizado_em->format('H:i') }}</span>
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-6 py-3 text-right font-mono text-gray-700 font-bold text-sm">
                                                                {{ number_format($apontamento->horas, 2, ',', '.') }} h
                                                            </td>
                                                            @if($contrato->valor_hora > 0)
                                                                <td class="px-6 py-3 text-right font-mono text-emerald-700 font-bold text-sm">
                                                                    R$ {{ number_format($apontamento->horas * $contrato->valor_hora, 2, ',', '.') }}
                                                                </td>
                                                            @endif
                                                            <td class="px-6 py-3 text-center">
                                                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $statusCor['bg'] }} {{ $statusCor['text'] }} border {{ $statusCor['border'] }}">
                                                                    {{ ucfirst($statusLabel) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                                        <tr class="font-semibold">
                                                            <td colspan="3" class="px-6 py-3 text-right text-gray-700">Subtotal do Mês:</td>
                                                            <td class="px-6 py-3 text-right font-mono text-gray-800">{{ number_format($totalMes, 2, ',', '.') }} h</td>
                                                            @if($contrato->valor_hora > 0)
                                                                <td class="px-6 py-3 text-right font-mono text-emerald-700">R$ {{ number_format($valorMes, 2, ',', '.') }}</td>
                                                            @endif
                                                            <td></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-400">
                                    Nenhuma atividade registrada neste contrato.
                                </div>
                            @endif
                        </div>

                        <!-- View 3: Por Status de Faturamento -->
                        <div x-show="viewType === 'status'" class="p-4">
                            @php
                                $apontamentosPorStatus = $contrato->apontamentos->groupBy('faturamento_status');
                                $statusOrder = ['nao_separado', 'separado', 'aprovado_cliente', 'faturado'];
                                $statusLabels = [
                                    'nao_separado' => 'Não Separado',
                                    'separado' => 'Separado',
                                    'aprovado_cliente' => 'Aprovado Cliente',
                                    'faturado' => 'Faturado'
                                ];
                                $statusColors = [
                                    'nao_separado' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'border' => 'border-gray-300'],
                                    'separado' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'border' => 'border-yellow-300'],
                                    'aprovado_cliente' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'border' => 'border-blue-300'],
                                    'faturado' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-300']
                                ];
                            @endphp

                            @if($contrato->apontamentos->count() > 0)
                                <div class="space-y-6">
                                    @foreach($statusOrder as $status)
                                        @php
                                            $apontamentosStatus = $apontamentosPorStatus->get($status, collect());
                                            $totalHoras = $apontamentosStatus->sum(function($a) { return $a->minutos_gastos / 60; });
                                            $colors = $statusColors[$status];
                                        @endphp
                                        
                                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg {{ $colors['border'] }} border-l-4">
                                            <div class="{{ $colors['bg'] }} {{ $colors['text'] }} px-6 py-4 font-semibold flex justify-between items-center">
                                                <div>
                                                    <span class="text-base font-bold">{{ $statusLabels[$status] }}</span>
                                                    <span class="text-xs opacity-75 ml-2">({{ $apontamentosStatus->count() }} {{ $apontamentosStatus->count() === 1 ? 'atividade' : 'atividades' }})</span>
                                                </div>
                                                <div class="text-lg font-bold">{{ number_format($totalHoras, 2, ',', '.') }} h</div>
                                            </div>
                                            @if($apontamentosStatus->count() > 0)
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full text-sm">
                                                        <thead class="bg-gray-50 border-b border-gray-200">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left font-semibold text-gray-700 w-28">Data</th>
                                                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Descrição</th>
                                                            <th class="px-6 py-3 text-center font-semibold text-gray-700 w-32">Horário</th>
                                                            <th class="px-6 py-3 text-right font-semibold text-gray-700 w-24">Tempo</th>
                                                            @if($contrato->valor_hora > 0)
                                                                <th class="px-6 py-3 text-right font-semibold text-gray-700 w-28">Valor</th>
                                                            @endif
                                                        </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                        @foreach($apontamentosStatus as $apontamento)
                                                            <tr class="hover:bg-gray-50 transition">
                                                                <td class="px-6 py-3 text-gray-600 whitespace-nowrap font-medium text-sm">
                                                                    {{ $apontamento->data_realizacao->format('d/m/Y') }}
                                                                </td>
                                                                <td class="px-6 py-3 text-gray-800 text-sm">
                                                                    {{ $apontamento->descricao }}
                                                                </td>
                                                                <td class="px-6 py-3 text-center text-gray-600 whitespace-nowrap font-mono text-xs">
                                                                    @if($apontamento->iniciado_em && $apontamento->finalizado_em)
                                                                        <span class="font-semibold">{{ $apontamento->iniciado_em->format('H:i') }}</span> a <span class="font-semibold">{{ $apontamento->finalizado_em->format('H:i') }}</span>
                                                                    @else
                                                                        <span class="text-gray-400">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-6 py-3 text-right font-mono text-gray-700 font-bold text-sm">
                                                                    {{ number_format($apontamento->horas, 2, ',', '.') }} h
                                                                </td>
                                                                @if($contrato->valor_hora > 0)
                                                                    <td class="px-6 py-3 text-right font-mono text-emerald-700 font-bold text-sm">
                                                                        R$ {{ number_format($apontamento->horas * $contrato->valor_hora, 2, ',', '.') }}
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                                            <tr class="font-semibold">
                                                                <td colspan="3" class="px-6 py-3 text-right text-gray-700">Subtotal:</td>
                                                                <td class="px-6 py-3 text-right font-mono text-gray-800">{{ number_format($totalHoras, 2, ',', '.') }} h</td>
                                                                @if($contrato->valor_hora > 0)
                                                                    <td class="px-6 py-3 text-right font-mono text-emerald-700">R$ {{ number_format($totalHoras * $contrato->valor_hora, 2, ',', '.') }}</td>
                                                                @endif
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="px-6 py-8 text-center text-gray-400 text-sm">
                                                    Nenhuma atividade neste status.
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-400">
                                    Nenhuma atividade registrada neste contrato.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($isLivre)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-slate-500 mt-8">
                    <div class="p-6 border-b border-gray-200 bg-slate-50">
                        <h3 class="text-lg font-bold text-slate-700">Ciclo de Faturamento</h3>
                        <p class="text-sm text-slate-500">Selecione apontamentos e avance o status interno: separar, aprovar e faturar.</p>
                    </div>

                    @can('gh.operacional')
                        <form action="{{ route('gestor-horas.faturamento-status', $contrato->id) }}" method="POST">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">Sel.</th>
                                        <th class="px-4 py-3 text-left">Data</th>
                                        <th class="px-4 py-3 text-left">Descrição</th>
                                        <th class="px-4 py-3 text-right">Tempo</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @forelse($contrato->apontamentos as $apontamento)
                                        <tr>
                                            <td class="px-4 py-3">
                                                @if(in_array($apontamento->faturamento_status, ['nao_separado', 'separado', 'aprovado_cliente']))
                                                    <input type="checkbox" name="apontamentos[]" value="{{ $apontamento->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ $apontamento->data_realizacao->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3 text-gray-800">{{ $apontamento->descricao }}</td>
                                            <td class="px-4 py-3 text-right font-mono text-gray-700">{{ number_format($apontamento->horas, 2, ',', '.') }} h</td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $statusLabel = str_replace('_', ' ', $apontamento->faturamento_status ?? 'nao_separado');
                                                    $statusClass = match($apontamento->faturamento_status) {
                                                        'separado' => 'bg-yellow-100 text-yellow-700',
                                                        'aprovado_cliente' => 'bg-blue-100 text-blue-700',
                                                        'faturado' => 'bg-green-100 text-green-700',
                                                        default => 'bg-gray-100 text-gray-700',
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">{{ ucfirst($statusLabel) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 italic">
                                                Nenhum apontamento encontrado para este contrato.
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="p-4 border-t border-gray-200 bg-white flex justify-end">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <button type="submit" name="acao" value="separar" class="bg-gray-900 hover:bg-gray-800 border border-gray-900 text-white font-bold py-2 px-4 rounded text-sm">
                                        Separar
                                    </button>
                                    <button type="submit" name="acao" value="aprovar" class="bg-blue-700 hover:bg-blue-600 border border-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                        Marcar Aprovado
                                    </button>
                                    <button type="submit" name="acao" value="faturar" class="bg-green-700 hover:bg-green-600 border border-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                        Marcar Faturado
                                    </button>
                                </div>
                            </div>
                        </form>

                        <form action="{{ route('gestor-horas.faturamento-status', $contrato->id) }}" method="POST" class="px-4 pb-4 bg-white">
                            @csrf
                            <input type="hidden" name="acao" value="gerar_email">
                            <button type="submit" class="!bg-indigo-700 hover:!bg-indigo-600 !border !border-indigo-700 !text-white font-bold py-2 px-4 rounded text-sm shadow-sm">
                                Gerar Texto de Aprovação dos Itens Separados
                            </button>
                        </form>

                        @if(session('email_aprovacao_texto'))
                            <div class="p-4 border-t border-gray-200 bg-indigo-50">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-bold text-indigo-800">Corpo do e-mail para aprovação de faturamento</p>
                                    <button type="button"
                                            class="bg-indigo-700 hover:bg-indigo-600 border border-indigo-700 text-white font-bold py-1 px-3 rounded text-xs"
                                            onclick="const campo = document.getElementById('email-aprovacao-faturamento'); campo.select(); document.execCommand('copy');">
                                        Copiar Texto
                                    </button>
                                </div>
                                <textarea id="email-aprovacao-faturamento"
                                          rows="10"
                                          class="w-full rounded border-indigo-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ session('email_aprovacao_texto') }}</textarea>
                            </div>
                        @endif
                    @else
                        <div class="p-6 text-sm text-gray-500">Apenas usuários operacionais podem separar apontamentos para faturamento.</div>
                    @endcan
                        </div>
                    </div>
                </div>
            @endif

            @can('gh.operacional')
                @if(auth()->user()->can('gh.operacional') && $contrato->status === 'ativo')
                    <div class="bg-gray-800 text-white overflow-hidden shadow-lg sm:rounded-lg mt-8">
                        <div class="p-6">
                            <div class="flex items-center mb-6">
                                <div class="p-2 bg-blue-600 rounded-lg mr-3 shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold">Novo Apontamento</h3>
                                    <p class="text-sm text-gray-400">Registre suas horas trabalhadas aqui.</p>
                                </div>
                                <a href="{{ route('gestor-horas.mobile.timer') }}"
                                              class="ml-auto text-xs font-bold bg-blue-700 hover:bg-blue-600 text-white border border-blue-700 px-3 py-2 rounded">
                                    MODO MOBILE
                                </a>
                            </div>

                            <form action="{{ route('gestor-horas.apontar', $contrato->id) }}" method="POST">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">DATA</label>
                                        <input type="date" name="data_realizacao" value="{{ date('Y-m-d') }}" required
                                               class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    </div>

                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">VINCULAR A ITEM
                                            (OPCIONAL)</label>
                                        <select name="gh_contrato_item_id"
                                                class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="">-- Atividade Geral / Suporte --</option>
                                            @foreach($contrato->itens as $item)
                                                <option
                                                    value="{{ $item->id }}">{{ Str::limit($item->titulo, 40) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">HORAS
                                            (DECIMAL)</label>
                                        <input type="number" step="0.1" name="horas_gastas" required
                                               placeholder="Ex: 1.5"
                                               class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    </div>

                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">DESCRIÇÃO</label>
                                        <div class="flex gap-2">
                                            <input type="text" name="descricao" required
                                                   placeholder="O que foi feito..."
                                                   class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <button type="submit"
                                                    class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded text-sm transition shadow-lg">
                                                SALVAR
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan

        </div>
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</x-app-layout>
