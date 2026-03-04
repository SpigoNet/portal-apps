<x-Mithril::layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-600 rounded-lg text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </div>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Novo Planejamento
                </h2>
                <p class="text-sm text-gray-500">Cadastrar despesa recorrente ou parcelamento</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-8">

                <form action="{{ route('mithril.pre-transacoes.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Descrição do Gasto</label>
                        <input type="text" name="descricao" required
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-medium transition-colors"
                               placeholder="Ex: Assinatura Netflix, Aluguel, etc">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Valor da Parcela (R$)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">R$</span>
                                <input type="text" name="valor_parcela" required
                                       class="pl-10 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-black text-right transition-colors"
                                       placeholder="0,00">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Dia do Vencimento</label>
                            <input type="number" name="dia_vencimento" min="1" max="31" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-bold transition-colors"
                                   placeholder="1 a 31">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Conta Relacionada</label>
                        <select name="conta_id" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-medium transition-colors">
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-4 border-y border-gray-50 dark:border-gray-700">
                        <div>
                            <span class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-3">Tipo de Operação</span>
                            <div class="flex flex-col gap-3">
                                <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="radio" name="operacao" value="debito" checked class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-700 dark:text-gray-200">Débito / Despesa</span>
                                        <span class="block text-[10px] text-gray-400">Saída de dinheiro da conta</span>
                                    </div>
                                </label>
                                <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="radio" name="operacao" value="credito" class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-700 dark:text-gray-200">Crédito / Receita</span>
                                        <span class="block text-[10px] text-gray-400">Entrada de dinheiro na conta</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-3">Modelo de Lançamento</span>
                            <div class="flex flex-col gap-3">
                                <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="radio" name="tipo" value="recorrente" checked onclick="toggleParcelas(false)" class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-700 dark:text-gray-200">Mensal (Fixo)</span>
                                        <span class="block text-[10px] text-gray-400">Repete todo mês indefinidamente</span>
                                    </div>
                                </label>
                                <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="radio" name="tipo" value="parcelada" onclick="toggleParcelas(true)" class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-700 dark:text-gray-200">Parcelado</span>
                                        <span class="block text-[10px] text-gray-400">Tem um número limitado de vezes</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="campos-parcelas" class="hidden animate-in fade-in slide-in-from-top-4 duration-300 p-5 bg-blue-50/50 dark:bg-gray-900/50 border border-blue-100 dark:border-blue-900 rounded-xl space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-blue-500 uppercase tracking-widest mb-2">Total de Parcelas</label>
                                <input type="number" name="total_parcelas" id="total_parcelas" min="1"
                                       class="mt-1 block w-full rounded-lg border-blue-300 dark:border-blue-800 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-black transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-blue-500 uppercase tracking-widest mb-2">Primeiro Vencimento</label>
                                <input type="date" name="data_inicio" id="data_inicio" value="{{ date('Y-m-d') }}"
                                       class="mt-1 block w-full rounded-lg border-blue-300 dark:border-blue-800 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-medium transition-colors">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-50 dark:border-gray-700">
                        <a href="{{ route('mithril.pre-transacoes.index') }}"
                           class="text-sm font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-black py-3 px-8 rounded-xl shadow-lg shadow-blue-200 dark:shadow-none transition uppercase tracking-widest text-xs">
                            Confirmar Cadastro
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function toggleParcelas(show) {
            const div = document.getElementById('campos-parcelas');
            const inputTotal = document.getElementById('total_parcelas');
            const inputData = document.getElementById('data_inicio');

            if (show) {
                div.classList.remove('hidden');
                inputTotal.required = true;
                inputData.required = true;
            } else {
                div.classList.add('hidden');
                inputTotal.required = false;
                inputData.required = false;
                inputTotal.value = '';
            }
        }
    </script>
</x-Mithril::layout>
