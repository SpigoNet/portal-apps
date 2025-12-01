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

            <div
                class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 {{ $contrato->saldo < 0 ? 'border-red-500' : 'border-blue-500' }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 divide-y md:divide-y-0 md:divide-x divide-gray-100">
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
                        <p class="text-xs text-gray-400 uppercase font-bold">Saldo Disponível</p>
                        <p class="text-2xl font-bold {{ $contrato->saldo < 0 ? 'text-red-500' : 'text-green-500' }}">
                            {{ number_format($contrato->saldo, 2, ',', '.') }} h
                        </p>
                    </div>
                </div>
            </div>

            @if($contrato->itens->count() > 0)
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
            @endif

            @php
                $apontamentosGerais = $contrato->apontamentos->whereNull('gh_contrato_item_id');
            @endphp

            @if($apontamentosGerais->count() > 0 || ($contrato->tipo == 'recorrente' && $contrato->itens->count() == 0))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-gray-400 mt-8">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-700">Atividades Gerais / Suporte</h3>
                        <p class="text-sm text-gray-500">Apontamentos avulsos não vinculados a um item específico do
                            escopo.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">
                                    Data
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">
                                    Descrição
                                </th>
                                <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">
                                    Tempo
                                </th>
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
                                        Nenhuma atividade geral avulsa registrada.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
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
</x-app-layout>
