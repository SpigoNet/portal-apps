<x-Mithril::layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-600 rounded-lg text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Editar Planejamento
                </h2>
                <p class="text-sm text-gray-500">Alterar configurações do gasto fixo ou parcelado</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 p-8">

                <form action="{{ route('mithril.pre-transacoes.update', $preTransacao->id) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Descrição do Gasto</label>
                        <input type="text" name="descricao" value="{{ $preTransacao->descricao }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-medium transition-colors">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Valor da Parcela (R$)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">R$</span>
                                <input type="text" name="valor_parcela" value="{{ number_format(abs($preTransacao->valor_parcela), 2, ',', '.') }}" required
                                       class="pl-10 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-black text-right transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Dia do Vencimento</label>
                            <input type="number" name="dia_vencimento" value="{{ $preTransacao->dia_vencimento }}" min="1" max="31" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-bold transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Conta Relacionada</label>
                        <select name="conta_id" required
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-medium transition-colors">
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}" {{ $preTransacao->conta_id == $conta->id ? 'selected' : '' }}>
                                    {{ $conta->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-4 border-y border-gray-50 dark:border-gray-700">
                        <div>
                            <span class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-3">Tipo de Operação</span>
                            <div class="flex flex-col gap-3">
                                <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="radio" name="operacao" value="debito" {{ $preTransacao->valor_parcela < 0 ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-700 dark:text-gray-200">Débito / Despesa</span>
                                        <span class="block text-[10px] text-gray-400">Saída de dinheiro da conta</span>
                                    </div>
                                </label>
                                <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="radio" name="operacao" value="credito" {{ $preTransacao->valor_parcela >= 0 ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500 h-4 w-4">
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
                                    <input type="radio" name="tipo" value="recorrente" {{ $preTransacao->tipo == 'recorrente' ? 'checked' : '' }} onclick="toggleParcelas(false)" class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-700 dark:text-gray-200">Mensal (Fixo)</span>
                                        <span class="block text-[10px] text-gray-400">Repete todo mês indefinidamente</span>
                                    </div>
                                </label>
                                <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                    <input type="radio" name="tipo" value="parcelada" {{ $preTransacao->tipo == 'parcelada' ? 'checked' : '' }} onclick="toggleParcelas(true)" class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-700 dark:text-gray-200">Parcelado</span>
                                        <span class="block text-[10px] text-gray-400">Tem um número limitado de vezes</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="campos-parcelas" class="{{ $preTransacao->tipo == 'recorrente' ? 'hidden' : '' }} animate-in fade-in slide-in-from-top-4 duration-300 p-5 bg-blue-50/50 dark:bg-gray-900/50 border border-blue-100 dark:border-blue-900 rounded-xl space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-blue-500 uppercase tracking-widest mb-2">Total de Parcelas</label>
                                <input type="number" name="total_parcelas" id="total_parcelas" value="{{ $preTransacao->total_parcelas }}" min="1"
                                       class="mt-1 block w-full rounded-lg border-blue-300 dark:border-blue-800 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-black transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-blue-500 uppercase tracking-widest mb-2">Primeiro Vencimento</label>
                                <input type="date" name="data_inicio" id="data_inicio" value="{{ $preTransacao->data_inicio ? $preTransacao->data_inicio->format('Y-m-d') : '' }}"
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
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 px-8 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-none transition uppercase tracking-widest text-xs">
                            Salvar Alterações
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function toggleParcelas(show) {
            const div = document.getElementById('campos-parcelas');
            if (show) div.classList.remove('hidden');
            else div.classList.add('hidden');
        }
    </script>
</x-Mithril::layout>
