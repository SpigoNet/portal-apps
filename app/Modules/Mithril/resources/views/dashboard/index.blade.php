<x-Mithril::layout>
    <x-slot name="header">
        {{ __('Lançamentos') }}
    </x-slot>

    <x-slot name="contextMenu">
        <x-dropdown-link :href="route('mithril.pre-transacoes.create')" class="text-blue-500">
            + Nova Transação
        </x-dropdown-link>
        <x-dropdown-link href="#">
            Exportar PDF
        </x-dropdown-link>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 px-2 border-l-4 border-blue-500">
                    Resumo das Contas
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($dadosContas as $conta)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                                <h4 class="font-bold text-lg text-gray-800 dark:text-gray-200">{{ $conta['nome'] }}</h4>
                            </div>
                            <div class="p-4 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Saldo Inicial:</span>
                                    <span class="{{ $conta['saldo_inicial'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                        R$ {{ number_format($conta['saldo_inicial'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Real (Hoje):</span>
                                    <span class="{{ $conta['real_hoje'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold text-base">
                                        R$ {{ number_format($conta['real_hoje'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Previsto (Hoje):</span>
                                    <span class="{{ $conta['previsto_hoje'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        R$ {{ number_format($conta['previsto_hoje'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-gray-500">Real (Fim Mês):</span>
                                    <span class="{{ $conta['real_fim_mes'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        R$ {{ number_format($conta['real_fim_mes'], 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Previsto (Fim Mês):</span>
                                    <span class="{{ $conta['previsto_fim_mes'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        R$ {{ number_format($conta['previsto_fim_mes'], 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full p-4 bg-white dark:bg-gray-800 rounded shadow text-center text-gray-500">
                            Nenhuma conta normal cadastrada.
                        </div>
                    @endforelse
                </div>
            </div>

            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 px-2 border-l-4 border-purple-500">
                    Resumo dos Cartões
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($dadosCartoes as $cartao)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-purple-500">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 flex justify-between items-center">
                                <h4 class="font-bold text-lg text-gray-800 dark:text-gray-200">{{ $cartao['nome'] }}</h4>
                                <span class="text-xs font-semibold px-2 py-1 bg-purple-100 text-purple-800 rounded">Crédito</span>
                            </div>
                            <div class="p-4 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Saldo Anterior:</span>
                                    <span class="text-red-600 font-semibold">
                                        R$ {{ number_format(abs($cartao['saldo_anterior']), 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Fatura Aberta:</span>
                                    <span class="text-red-600 font-semibold">
                                        R$ {{ number_format(abs($cartao['fatura_aberta']), 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between border-t pt-2 mt-2">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Total a Pagar:</span>
                                    <span class="text-red-600 font-bold text-base">
                                        R$ {{ number_format(abs($cartao['total_pagar']), 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 p-3 text-right">
                                <a href="{{ route('mithril.faturas.show', $cartao['id']) }}" class="text-purple-600 hover:text-purple-900 font-medium text-sm">
                                    Ver Fatura &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full p-4 bg-white dark:bg-gray-800 rounded shadow text-center text-gray-500">
                            Nenhum cartão de crédito cadastrado.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-mithril::layout>
