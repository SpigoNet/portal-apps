<x-Mithril::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Adicionar Nova Transação
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <form action="{{ route('mithril.transacao.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                            <input type="text" name="descricao" id="descricao" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('descricao') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="valor" class="block text-sm font-medium text-gray-700">Valor (R$)</label>
                                <input type="text" name="valor" id="valor" required placeholder="0,00"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('valor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="data_efetiva" class="block text-sm font-medium text-gray-700">Data</label>
                                <input type="date" name="data_efetiva" id="data_efetiva" value="{{ date('Y-m-d') }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('data_efetiva') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="conta_id" class="block text-sm font-medium text-gray-700">Conta</label>
                            <select name="conta_id" id="conta_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Selecione...</option>
                                @foreach($contas as $conta)
                                    <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                                @endforeach
                            </select>
                            @error('conta_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-6">
                            <span class="block text-sm font-medium text-gray-700 mb-2">Tipo de Operação</span>
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <input type="radio" id="operacao-debito" name="operacao" value="debito" checked
                                           class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    <label for="operacao-debito" class="ml-2 block text-sm text-gray-700">
                                        Débito / Despesa
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="operacao-credito" name="operacao" value="credito"
                                           class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    <label for="operacao-credito" class="ml-2 block text-sm text-gray-700">
                                        Crédito / Receita
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('mithril.lancamentos.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
                                Cancelar
                            </a>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                                Salvar Transação
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-Mithril::layout>
