<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de Horas - {{ $cliente->nome }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="space-y-6">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 {{ $contrato->saldo < 0 ? 'border-red-500' : 'border-blue-500' }}">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">{{ $contrato->titulo }}</h2>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ ucfirst($contrato->tipo) }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100 bg-gray-50">
                        <div class="p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Consumido</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">
                                {{ number_format($contrato->horas_consumidas, 2, ',', '.') }} h
                            </p>
                        </div>
                        <div class="p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Total Contratado</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1">
                                {{ number_format($contrato->horas_contratadas, 2, ',', '.') }} h
                            </p>
                        </div>
                        <div class="p-4 text-center">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Saldo Restante</p>
                            <p class="text-2xl font-bold {{ $contrato->saldo < 0 ? 'text-red-500' : 'text-green-500' }} mt-1">
                                {{ number_format($contrato->saldo, 2, ',', '.') }} h
                            </p>
                        </div>
                    </div>
                </div>

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
                                        <p class="text-sm text-gray-400 italic">Nenhum registro de horas específico para este item.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @php
                    $apontamentosGerais = $contrato->apontamentos->whereNull('gh_contrato_item_id');
                @endphp

                @if($apontamentosGerais->count() > 0 || ($contrato->tipo == 'recorrente' && $contrato->itens->count() == 0))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-gray-400 mt-8">
                        <div class="p-6 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-bold text-gray-700">Atividades Gerais / Suporte</h3>
                            <p class="text-sm text-gray-500">Tarefas de manutenção ou suporte não vinculadas ao escopo fechado.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-white">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider w-32">Data</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider w-24">Tempo</th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($apontamentosGerais as $apontamento)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                            {{ $apontamento->data_realizacao->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-900">
                                            {{ $apontamento->descricao }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 text-right font-mono font-bold">
                                            {{ number_format($apontamento->horas, 2, ',', '.') }} h
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-6 text-center text-gray-400 italic">
                                            Nenhuma atividade geral registrada.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

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
