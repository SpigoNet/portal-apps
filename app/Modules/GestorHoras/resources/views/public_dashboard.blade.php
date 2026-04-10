<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de Horas - {{ $cliente->nome }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">

<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                Painel de Transparência
            </h1>
            <p class="text-sm text-gray-500 mt-1">Cliente: <span class="font-semibold text-gray-700">{{ $cliente->nome }}</span></p>
        </div>
        <div class="text-right">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    Atualizado em {{ date('d/m/Y H:i') }}
                </span>
        </div>
    </div>
</div>

<div class="py-10">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-12">

        @forelse($contratos as $contrato)
            @php
                $isLivre = $contrato->tipo === 'livre';
                $totalSeparado = $contrato->apontamentos->where('faturamento_status', 'separado')->count();
                $totalAprovado = $contrato->apontamentos->where('faturamento_status', 'aprovado_cliente')->count();
                $totalFaturado = $contrato->apontamentos->where('faturamento_status', 'faturado')->count();
            @endphp
            <div class="space-y-6">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 {{ $isLivre ? 'border-slate-500' : ($contrato->saldo < 0 ? 'border-red-500' : 'border-blue-500') }}">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">{{ $contrato->titulo }}</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-gray-100 bg-gray-50">
                        <div class="p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Consumido</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">
                                {{ number_format($contrato->horas_consumidas, 2, ',', '.') }} h
                            </p>
                        </div>
                        <div class="p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Valor Hora</p>
                            <p class="text-2xl font-bold text-emerald-700 mt-1">
                                R$ {{ number_format($contrato->valor_hora, 2, ',', '.') }}
                            </p>
                        </div>
                        <div class="p-4 text-center">
                            @if($isLivre)
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Separados p/ faturamento</p>
                                <p class="text-2xl font-bold text-slate-700 mt-1">
                                    {{ $totalSeparado }}
                                </p>
                            @else
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Total Contratado</p>
                                <p class="text-2xl font-bold text-gray-700 mt-1">
                                    {{ number_format($contrato->horas_contratadas, 2, ',', '.') }} h
                                </p>
                            @endif
                        </div>
                        <div class="p-4 text-center">
                            @if($isLivre)
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Aprovados/Faturados</p>
                                <p class="text-lg font-bold text-green-700 mt-1">
                                    {{ $totalAprovado }} aprovado(s) / {{ $totalFaturado }} faturado(s)
                                </p>
                            @else
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Saldo Restante</p>
                                <p class="text-2xl font-bold {{ $contrato->saldo < 0 ? 'text-red-500' : 'text-green-500' }} mt-1">
                                    {{ number_format($contrato->saldo, 2, ',', '.') }} h
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

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
                            @if($contrato->itens->count() > 0)
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 px-1 border-b border-gray-300 pb-2">Progresso do Escopo</h3>

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

                                <div class="p-6">
                                    <div class="flex flex-col md:flex-row justify-between md:items-center mb-2">
                                        <h4 class="font-bold text-gray-800 text-lg">{{ $item->titulo }}</h4>
                                        <div class="text-sm">
                                                <span class="font-bold {{ $realizado > $estimado ? 'text-red-600' : 'text-gray-700' }}">
                                                    {{ number_format($realizado, 2, ',', '.') }}h
                                                </span>
                                            <span class="text-gray-400"> / {{ number_format($estimado, 2, ',', '.') }}h</span>
                                        </div>
                                    </div>

                                    <div class="w-full bg-gray-200 rounded-full h-3 mb-3">
                                        <div class="{{ $corBarra }} h-3 rounded-full" style="width: {{ min($porcentagem, 100) }}%"></div>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ $item->descricao }}</p>
                                </div>

                                <div class="bg-gray-50 border-t border-gray-200 p-4 sm:p-6">
                                    <h5 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Entregas realizadas neste item
                                    </h5>

                                    @if($item->apontamentos->count() > 0)
                                        <div class="bg-white rounded-md border border-gray-200 overflow-hidden">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-100 text-gray-500">
                                                <tr>
                                                    <th class="px-4 py-2 text-left font-medium w-32">Data</th>
                                                    <th class="px-4 py-2 text-left font-medium">O que foi feito</th>
                                                    <th class="px-4 py-2 text-center font-medium w-32">Horário</th>
                                                    <th class="px-4 py-2 text-right font-medium w-24">Tempo</th>
                                                    @if($contrato->valor_hora > 0)
                                                        <th class="px-4 py-2 text-right font-medium w-28">Valor</th>
                                                    @endif
                                                    <th class="px-4 py-2 text-center font-medium w-32">Status</th>
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                @foreach($item->apontamentos as $log)
                                                    @php
                                                        $statusAtual = $log->faturamento_status ?? 'nao_separado';
                                                        $statusCor = match($statusAtual) {
                                                            'separado' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-300'],
                                                            'aprovado_cliente' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-300'],
                                                            'faturado' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-300'],
                                                            default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'border' => 'border-gray-300']
                                                        };
                                                        $statusLabel = str_replace('_', ' ', $statusAtual);
                                                    @endphp
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2 text-gray-600 whitespace-nowrap font-medium">{{ $log->data_realizacao->format('d/m/Y') }}</td>
                                                        <td class="px-4 py-2 text-gray-800">{{ $log->descricao }}</td>
                                                        <td class="px-4 py-2 text-center text-gray-600 whitespace-nowrap font-mono text-xs">
                                                            @if($log->iniciado_em && $log->finalizado_em)
                                                                <span class="font-semibold">{{ $log->iniciado_em->format('H:i') }}</span> a <span class="font-semibold">{{ $log->finalizado_em->format('H:i') }}</span>
                                                            @else
                                                                <span class="text-gray-400">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-right font-mono text-gray-600 font-bold">{{ number_format($log->horas, 2, ',', '.') }}</td>
                                                        @if($contrato->valor_hora > 0)
                                                            <td class="px-4 py-2 text-right font-mono text-emerald-700 font-bold">R$ {{ number_format($log->horas * $contrato->valor_hora, 2, ',', '.') }}</td>
                                                        @endif
                                                        <td class="px-4 py-2 text-center">
                                                            <span class="inline-block px-2 py-1 rounded-full text-xs font-bold {{ $statusCor['bg'] }} {{ $statusCor['text'] }} border {{ $statusCor['border'] }}">
                                                                {{ ucfirst($statusLabel) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-400 italic">Nenhum registro de horas específico para este item.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
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
                        'nao_separado' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'border' => 'border-gray-300', 'card-border' => 'border-gray-200'],
                        'separado' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'border' => 'border-yellow-300', 'card-border' => 'border-yellow-200'],
                        'aprovado_cliente' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'border' => 'border-blue-300', 'card-border' => 'border-blue-200'],
                        'faturado' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-300', 'card-border' => 'border-green-200']
                    ];
                    
                    $statusResumos = [];
                    $totalGeralHoras = 0;
                    $totalGeralValor = 0;
                    
                    foreach ($statusOrder as $status) {
                        $items = $apontamentosPorStatus->get($status, collect());
                        $horas = $items->sum(function($a) { return $a->minutos_gastos / 60; });
                        $valor = $horas * ($contrato->valor_hora ?? 0);
                        $statusResumos[$status] = [
                            'count' => $items->count(),
                            'horas' => $horas,
                            'valor' => $valor
                        ];
                        $totalGeralHoras += $horas;
                        $totalGeralValor += $valor;
                    }
                @endphp

                @if($contrato->apontamentos->count() > 0)
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-900 px-1 border-b-2 border-gray-300 pb-3">Status de Faturamento das Horas</h3>
                        
                        @if($contrato->apontamentos->count() > 0)
                            <!-- Resumo no Topo -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                @foreach($statusOrder as $status)
                                    @php
                                        $resumo = $statusResumos[$status];
                                        $colors = $statusColors[$status];
                                    @endphp
                                    @if($resumo['count'] > 0)
                                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg {{ $colors['card-border'] }} border-2">
                                            <div class="{{ $colors['bg'] }} {{ $colors['text'] }} px-4 py-3 text-center">
                                                <p class="text-xs font-bold uppercase tracking-widest opacity-80">{{ $statusLabels[$status] }}</p>
                                                <p class="text-2xl font-bold mt-1">{{ number_format($resumo['horas'], 2, ',', '.') }}</p>
                                                <p class="text-xs opacity-75 mt-1">{{ $resumo['count'] }} {{ $resumo['count'] === 1 ? 'item' : 'itens' }}</p>
                                            </div>
                                            @if($contrato->valor_hora > 0)
                                                <div class="px-4 py-2 text-center text-sm font-semibold text-emerald-700 bg-emerald-50">
                                                    R$ {{ number_format($resumo['valor'], 2, ',', '.') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <!-- Totalizador Geral -->
                            <div class="bg-white overflow-hidden shadow-md sm:rounded-lg border-2 border-gray-300">
                                <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-widest opacity-80">Total Geral de Horas</p>
                                        <p class="text-3xl font-bold mt-1">{{ number_format($totalGeralHoras, 2, ',', '.') }} h</p>
                                    </div>
                                    @if($contrato->valor_hora > 0)
                                        <div class="text-right">
                                            <p class="text-xs font-bold uppercase tracking-widest opacity-80">Valor Total Previsto</p>
                                            <p class="text-3xl font-bold text-emerald-400 mt-1">R$ {{ number_format($totalGeralValor, 2, ',', '.') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Detalhes por Status -->
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
                                        <div class="flex gap-4 items-center">
                                            <div class="text-lg font-bold">{{ number_format($totalHoras, 2, ',', '.') }} h</div>
                                            @if($contrato->valor_hora > 0)
                                                <div class="text-lg font-bold">R$ {{ number_format($totalHoras * $contrato->valor_hora, 2, ',', '.') }}</div>
                                            @endif
                                        </div>
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
                                                    <th class="px-6 py-3 text-center font-semibold text-gray-700 w-40">Status Atual</th>
                                                    @if(in_array($status, ['separado', 'aprovado_cliente', 'faturado']))
                                                        <th class="px-6 py-3 text-center font-semibold text-gray-700 w-40">Status desde</th>
                                                    @endif
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                @foreach($apontamentosStatus as $apontamento)
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
                                                        @if(in_array($status, ['separado', 'aprovado_cliente', 'faturado']))
                                                            <td class="px-6 py-3 text-center text-gray-600 text-xs">
                                                                @if($apontamento->faturamento_selecionado_em)
                                                                    <div class="font-semibold">{{ $apontamento->faturamento_selecionado_em->format('d/m/Y H:i') }}</div>
                                                                    @if($apontamento->selecionadoPor)
                                                                        <div class="text-gray-500">por {{ $apontamento->selecionadoPor->name ?? 'Admin' }}</div>
                                                                    @endif
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
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
                                                        <td colspan="{{ in_array($status, ['separado', 'aprovado_cliente', 'faturado']) ? '2' : '1' }}"></td>
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
                        @else
                            <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-400">
                                Nenhuma atividade registrada neste contrato.
                            </div>
                        @endif
                    </div>
                @endif
                    </div>
                </div>

            </div>

            @if(!$loop->last)
                <hr class="border-gray-300 my-12 border-dashed">
            @endif
        @empty
            <div class="text-center py-20 bg-white rounded-lg shadow-sm">
                <p class="text-gray-500 text-lg">Nenhum contrato ativo encontrado para visualização.</p>
            </div>
        @endforelse

    </div>
</div>
</body>
</html>
