<x-Mithril::layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gray-800 rounded-lg text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Fechamento Mensal
                </h2>
                <p class="text-sm text-gray-500">Conciliação de saldos e encerramento de período</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-lg shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                @foreach($dadosFechamento as $item)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 flex flex-col h-full hover:shadow-md transition">

                        <div class="p-6 border-b border-gray-50 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900">
                            <h3 class="font-black text-gray-800 dark:text-gray-200 flex items-center gap-2 uppercase tracking-tighter">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                {{ $item->conta->nome }}
                            </h3>
                            <div class="text-[10px] text-gray-400 mt-2 font-bold uppercase tracking-widest">
                                @if($item->ultimo_fechamento)
                                    Último: {{ str_pad($item->ultimo_fechamento->mes, 2, '0', STR_PAD_LEFT) }}/{{ $item->ultimo_fechamento->ano }}
                                    • Saldo: R$ {{ number_format($item->ultimo_fechamento->saldo_final, 2, ',', '.') }}
                                @else
                                    <span class="text-orange-400">Sem registros anteriores</span>
                                @endif
                            </div>
                        </div>

                        <div class="p-6 flex-grow flex flex-col justify-center space-y-6">
                            <div class="text-center">
                                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Referência do Fechamento</span>
                                <span class="block text-2xl font-black text-gray-800 dark:text-gray-100 uppercase italic">
                                {{ $item->alvo_data_formatada }}
                            </span>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-sm space-y-3 border border-gray-100 dark:border-gray-600">
                                <div class="flex justify-between items-center text-gray-500">
                                    <span class="text-xs">Saldo Inicial</span>
                                    <span class="font-mono">R$ {{ number_format($item->ultimo_fechamento ? $item->ultimo_fechamento->saldo_final : $item->conta->saldo_inicial, 2, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Movimentação Bruta</span>
                                    <span class="font-mono font-bold {{ $item->movimentacoes >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $item->movimentacoes >= 0 ? '+' : '' }} R$ {{ number_format($item->movimentacoes, 2, ',', '.') }}
                                </span>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-600 pt-3 flex justify-between items-center">
                                    <span class="text-xs font-black uppercase text-gray-400">Saldo Sugerido</span>
                                    <span class="font-black text-blue-600 text-lg">R$ {{ number_format($item->saldo_sugerido, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700">
                            <form action="{{ route('mithril.fechamentos.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="conta_id" value="{{ $item->conta->id }}">
                                <input type="hidden" name="mes" value="{{ $item->alvo_mes }}">
                                <input type="hidden" name="ano" value="{{ $item->alvo_ano }}">

                                <div class="space-y-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">
                                        Saldo Final Real em Conta
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">R$</span>
                                            <input type="number" step="0.01" name="saldo_final"
                                                   value="{{ number_format($item->saldo_sugerido, 2, '.', '') }}"
                                                   class="block w-full pl-10 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 font-black text-right text-lg transition-colors"
                                                   required>
                                        </div>

                                        <button type="submit" class="px-5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-lg shadow-blue-200 dark:shadow-none transition flex items-center justify-center" title="Confirmar Fechamento">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </div>
                                    <p class="text-[9px] text-gray-400 text-center leading-tight">
                                        Verifique seu extrato bancário e confirme se o valor bate exatamente com o saldo final do mês.
                                    </p>
                                </div>
                            </form>
                        </div>

                    </div>
                @endforeach

            </div>
        </div>
    </div>
</x-Mithril::layout>
