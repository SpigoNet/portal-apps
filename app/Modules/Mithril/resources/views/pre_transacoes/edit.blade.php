<x-Mithril::layout>
    <x-slot name="header">
        {{ __('Editar Pré-Transação') }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('mithril.pre-transacoes.update', $preTransacao->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                        <input type="text" name="descricao" value="{{ $preTransacao->descricao }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor (R$)</label>
                            <input type="text" name="valor_parcela" value="{{ number_format(abs($preTransacao->valor_parcela), 2, ',', '.') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dia Vencimento</label>
                            <input type="number" name="dia_vencimento" value="{{ $preTransacao->dia_vencimento }}" min="1" max="31" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Padrão</label>
                        <select name="conta_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}" {{ $preTransacao->conta_id == $conta->id ? 'selected' : '' }}>
                                    {{ $conta->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Operação</span>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="operacao" value="debito" {{ $preTransacao->valor_parcela < 0 ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Débito</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="operacao" value="credito" {{ $preTransacao->valor_parcela >= 0 ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Crédito</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</span>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="tipo" value="recorrente" {{ $preTransacao->tipo == 'recorrente' ? 'checked' : '' }} onclick="toggleParcelas(false)" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Recorrente</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="tipo" value="parcelada" {{ $preTransacao->tipo == 'parcelada' ? 'checked' : '' }} onclick="toggleParcelas(true)" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Parcelada</span>
                            </label>
                        </div>
                    </div>

                    <div id="campos-parcelas" class="{{ $preTransacao->tipo == 'recorrente' ? 'hidden' : '' }} border-l-4 border-blue-500 pl-4 py-2 bg-gray-50 dark:bg-gray-700 mb-4 rounded-r">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Parcelas</label>
                                <input type="number" name="total_parcelas" id="total_parcelas" value="{{ $preTransacao->total_parcelas }}" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" value="{{ $preTransacao->data_inicio ? $preTransacao->data_inicio->format('Y-m-d') : '' }}" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <a href="{{ route('mithril.pre-transacoes.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar Alterações</button>
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
