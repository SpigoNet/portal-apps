<x-Mithril::layout>
    <x-slot name="header">
        {{ __('Nova Pré-Transação') }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('mithril.pre-transacoes.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                        <input type="text" name="descricao" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor (R$)</label>
                            <input type="text" name="valor_parcela" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0,00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dia Vencimento</label>
                            <input type="number" name="dia_vencimento" min="1" max="31" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Padrão</label>
                        <select name="conta_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Operação</span>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="operacao" value="debito" checked class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Débito / Despesa</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="operacao" value="credito" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Crédito / Receita</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</span>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="tipo" value="recorrente" checked onclick="toggleParcelas(false)" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Recorrente (Mensal)</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="tipo" value="parcelada" onclick="toggleParcelas(true)" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Parcelada</span>
                            </label>
                        </div>
                    </div>

                    <div id="campos-parcelas" class="hidden border-l-4 border-blue-500 pl-4 py-2 bg-gray-50 dark:bg-gray-700 mb-4 rounded-r">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Parcelas</label>
                                <input type="number" name="total_parcelas" id="total_parcelas" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" value="{{ date('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <a href="{{ route('mithril.pre-transacoes.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
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
