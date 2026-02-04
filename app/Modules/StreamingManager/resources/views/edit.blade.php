<x-StreamingManager::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Streaming') }}: {{ $streaming->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('streaming-manager.update', $streaming) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome do Streaming</label>
                        <input type="text" name="name" value="{{ $streaming->name }}"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Usuário/Email de Login</label>
                            <input type="text" name="username" value="{{ $streaming->username }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Senha de Login</label>
                            <input type="text" name="password" value="{{ $streaming->password }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-yellow-600 mt-1">⚠ Atenção: Esta senha não é criptografada. Salve
                                apenas para facilitar o compartilhamento.</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custo Mensal (R$)</label>
                        <input type="number" step="0.01" name="monthly_cost" value="{{ $streaming->monthly_cost }}"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                    <div class="flex justify-between pt-4">
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                            Atualizar Streaming
                        </button>
                    </div>
                </form>

                <form action="{{ route('streaming-manager.destroy', $streaming) }}" method="POST"
                    class="mt-8 border-t pt-4">
                    @csrf @method('DELETE')
                    <p class="text-sm text-gray-500 mb-2">Zona de Perigo: Isso excluirá permanentemente o streaming e
                        todo o histórico de pagamentos.</p>
                    <button type="submit" onclick="return confirm('Tem certeza?')"
                        class="text-red-600 hover:text-red-900 font-bold">
                        Excluir Streaming
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-StreamingManager::layout>