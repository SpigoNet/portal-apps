<x-Mithril::layout>
    <x-slot name="header">
        {{ __('Fechamento de Mês') }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                @foreach($dadosFechamento as $item)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-full border-t-4 border-blue-500">

                        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <h3 class="font-bold text-lg text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                {{ $item->conta->nome }}
                            </h3>
                            <div class="text-sm text-gray-500 mt-1">
                                @if($item->ultimo_fechamento)
                                    Último fechamento: {{ str_pad($item->ultimo_fechamento->mes, 2, '0', STR_PAD_LEFT) }}/{{ $item->ultimo_fechamento->ano }}
                                    <br>
                                    Saldo: <strong>R$ {{ number_format($item->ultimo_fechamento->saldo_final, 2, ',', '.') }}</strong>
                                @else
                                    <span class="text-orange-500">Nenhum fechamento anterior.</span>
                                @endif
                            </div>
                        </div>

                        <div class="p-6 flex-grow flex flex-col justify-center space-y-4">
                            <div class="text-center">
                                <span class="block text-sm font-medium text-gray-500">Fechando Mês de:</span>
                                <span class="block text-xl font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                                {{ $item->alvo_data_formatada }}
                            </span>
                            </div>

                            <div class="bg-blue-50 dark:bg-gray-700 rounded p-3 text-sm space-y-1">
                                <div class="flex justify-between">
                                    <span>Saldo Anterior:</span>
                                    <span>R$ {{ number_format($item->ultimo_fechamento ? $item->ultimo_fechamento->saldo_final : $item->conta->saldo_inicial, 2, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Movimentações:</span>
                                    <span class="{{ $item->movimentacoes >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                    R$ {{ number_format($item->movimentacoes, 2, ',', '.') }}
                                </span>
                                </div>
                                <div class="border-t border-gray-300 dark:border-gray-600 pt-1 flex justify-between font-bold">
                                    <span>Calculado:</span>
                                    <span>R$ {{ number_format($item->saldo_sugerido, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                            <form action="{{ route('mithril.fechamentos.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="conta_id" value="{{ $item->conta->id }}">
                                <input type="hidden" name="mes" value="{{ $item->alvo_mes }}">
                                <input type="hidden" name="ano" value="{{ $item->alvo_ano }}">

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Saldo Final Real (R$)
                                </label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.01" name="saldo_final"
                                           value="{{ number_format($item->saldo_sugerido, 2, '.', '') }}"
                                           class="block w-full rounded-md border-gray-300 dark:bg-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold text-right"
                                           required>

                                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded shadow flex items-center" title="Confirmar Fechamento">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-2 text-center">
                                    Ajuste o valor se houver divergência de centavos com o banco.
                                </p>
                            </form>
                        </div>

                    </div>
                @endforeach

            </div>
        </div>
    </div>
</x-Mithril::layout>
