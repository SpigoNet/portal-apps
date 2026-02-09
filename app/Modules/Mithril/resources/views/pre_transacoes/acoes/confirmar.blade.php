<x-Mithril::layout>
    <x-slot name="header">
        Confirmar Fatura: {{ $preTransacao->descricao }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <p class="text-sm text-gray-500 mb-4">
                    Este é o <strong>Passo 1</strong>. Confirme o valor e a data de vencimento que vieram na fatura.
                    Isto atualizará a previsão para os próximos meses.
                </p>

                <form action="{{ route('mithril.pre-transacoes.confirmar', $preTransacao->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    <input type="hidden" name="ano" value="{{ $ano }}">
                    <input type="hidden" name="conta_id" value="{{ $contaId }}">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor da Fatura
                            (R$)</label>
                        <input type="number" step="0.01" name="valor"
                            value="{{ number_format($preTransacao->valor_parcela, 2, '.', '') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de
                            Vencimento</label>
                        <input type="date" name="data_vencimento" value="{{ $dataSugerida->format('Y-m-d') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <a href="{{ url()->previous() }}"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Confirmar Dados
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-Mithril::layout>