<x-Mithril::layout>
    <x-slot name="header">
        {{ __('Lançamentos') }}
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="GET" action="{{ route('mithril.lancamentos.index') }}"
                          class="flex flex-wrap gap-4 items-end">

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mês/Ano</label>
                            <div class="flex">
                                <select name="mes"
                                        class="rounded-l-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $m == $mes ? 'selected' : '' }}>
                                            {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" name="ano" value="{{ $ano }}"
                                       class="rounded-r-md border-l-0 border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-24 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Conta</label>
                            <select name="conta_id"
                                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 w-48 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todas</option>
                                @foreach($contas as $c)
                                    <option value="{{ $c->id }}" {{ $contaId == $c->id ? 'selected' : '' }}>
                                        {{ $c->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Filtrar
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Conta</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo Efet.</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo Prev.</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

                        <tr class="bg-gray-100 dark:bg-gray-900 font-bold border-l-4 border-transparent">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">Dia 01</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100" colspan="2">Saldo Anterior / Inicial</td>
                            <td class="px-6 py-4 text-right text-sm"></td>
                            <td class="px-6 py-4 text-right text-sm {{ $saldoInicial >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                R$ {{ number_format($saldoInicial, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm {{ $saldoInicial >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                R$ {{ number_format($saldoInicial, 2, ',', '.') }}
                            </td>
                            <td colspan="2"></td>
                        </tr>

                        @forelse($listaCombinada as $index => $item)
                            @php
                                // Lógica para saber se é o último item do dia
                                $proximoItem = $listaCombinada->get($index + 1);
                                $ultimoDoDia = !$proximoItem || $proximoItem->data_efetiva->format('Y-m-d') != $item->data_efetiva->format('Y-m-d');

                                // ALTERAÇÃO AQUI: Em vez de mudar o background, mudamos a cor da borda lateral
                                $borderClass = 'border-transparent'; // Padrão (Efetivado/Real)

                                if ($item->status === 'confirmado') {
                                    // Amarelo/Dourado para Confirmado
                                    $borderClass = 'border-yellow-400 dark:border-yellow-500 bg-yellow-50/30 dark:bg-yellow-900/10';
                                } elseif ($item->status === 'pendente') {
                                    // Laranja para Pendente
                                    $borderClass = 'border-orange-400 dark:border-orange-500 bg-orange-50/30 dark:bg-orange-900/10';
                                }
                            @endphp

                            <tr class="border-l-4 {{ $borderClass }} hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $item->data_efetiva->format('d/m') }}
                                    <span class="text-xs text-gray-400 ml-1">({{ $item->data_efetiva->format('D') }})</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $item->descricao }}
                                    @if($item->meta_parcela)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        {{ $item->meta_parcela }}
                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->conta->nome }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold {{ $item->valor >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    R$ {{ number_format($item->valor, 2, ',', '.') }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-400">
                                    @if($ultimoDoDia)
                                        <span class="{{ $item->saldo_acumulado_efetivado >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        R$ {{ number_format($item->saldo_acumulado_efetivado, 2, ',', '.') }}
                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-400">
                                    @if($ultimoDoDia)
                                        <span class="{{ $item->saldo_acumulado_previsto >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        R$ {{ number_format($item->saldo_acumulado_previsto, 2, ',', '.') }}
                    </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($item->status === 'efetivado')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Efetivado
                    </span>
                                    @elseif($item->status === 'confirmado')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Confirmado
                    </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                        Pendente
                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($item->tipo === 'projetado')
                                        @if($item->status === 'pendente')
                                            <a href="{{ route('mithril.pre-transacoes.form-confirmar', ['id' => $item->pre_transacao_id, 'mes' => $mes, 'ano' => $ano, 'conta_id' => $contaId]) }}"
                                               class="text-orange-600 hover:text-orange-900 font-bold flex items-center justify-end gap-1" title="Confirmar">
                                                <span>Confirmar</span>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                            </a>
                                        @elseif($item->status === 'confirmado')
                                            <form action="{{ route('mithril.pre-transacoes.efetivar', ['id' => $item->pre_transacao_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="mes" value="{{ $mes }}">
                                                <input type="hidden" name="ano" value="{{ $ano }}">
                                                <input type="hidden" name="conta_id" value="{{ $contaId }}">
                                                <button type="submit" class="text-green-600 hover:text-green-900 font-bold flex items-center justify-end gap-1" onclick="return confirm('Confirmar pagamento desta conta?')" title="Pagar">
                                                    <span>Pagar</span>
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('mithril.pre-transacoes.form-confirmar', ['id' => $item->pre_transacao_id, 'mes' => $mes, 'ano' => $ano, 'conta_id' => $contaId]) }}" class="text-gray-400 hover:text-gray-600 text-xs ml-2">(Editar)</a>
                                        @endif
                                    @else
                                        <span class="text-gray-400 flex items-center justify-end gap-1 cursor-default">
                        <span>Pago</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                    Nenhum lançamento encontrado para este período.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-Mithril::layout>
