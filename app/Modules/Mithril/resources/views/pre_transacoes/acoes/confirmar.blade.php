<x-Mithril::layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-500 rounded-lg text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Confirmar Lançamento
                </h2>
                <p class="text-sm text-gray-500">{{ $preTransacao->descricao }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-8">

                <div class="mb-8 p-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 rounded-r-lg">
                    <p class="text-xs text-amber-800 dark:text-amber-200 leading-relaxed font-medium">
                        <strong>Passo 1:</strong> Verifique o valor e a data exata que consta no seu boleto ou fatura.
                        Isso ajustará a previsão financeira para este mês e os próximos.
                    </p>
                </div>

                <form action="{{ route('mithril.pre-transacoes.confirmar', $preTransacao->id) }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    <input type="hidden" name="ano" value="{{ $ano }}">
                    <input type="hidden" name="conta_id" value="{{ $contaId }}">

                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Valor Real da Fatura</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">R$</span>
                            <input type="number" step="0.01" name="valor"
                                value="{{ number_format($preTransacao->valor_parcela, 2, '.', '') }}"
                                class="pl-10 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-lg font-black text-right transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Data Exata do Vencimento</label>
                        <input type="date" name="data_vencimento" value="{{ $dataSugerida->format('Y-m-d') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm font-bold text-center transition-colors">
                    </div>

                    <div class="flex flex-col gap-3 mt-8 pt-6 border-t border-gray-50 dark:border-gray-700">
                        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-black py-3 rounded-xl shadow-lg shadow-amber-200 dark:shadow-none transition uppercase tracking-widest text-xs">
                            Confirmar Valor e Data
                        </button>
                        <a href="{{ route('mithril.lancamentos.index', ['mes' => $mes, 'ano' => $ano, 'conta_id' => $contaId]) }}"
                            class="text-center text-xs font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest py-2 transition">
                            Voltar ao fluxo
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-Mithril::layout>
