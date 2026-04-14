<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        Modelos de Job
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Gerencie modelos com variáveis dinâmicas (use __nome_variavel__ no JSON)
                    </p>
                </div>
                <a href="{{ route('comfy-queue.index') }}" class="text-sm text-indigo-600 hover:underline">
                    ← Voltar
                </a>
            </div>

            <div class="mb-4">
                <a href="{{ route('comfy-queue.job-models.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    + Novo Modelo
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 rounded p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variáveis</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($modelos as $modelo)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $modelo->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $modelo->nome }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @forelse($modelo->variaveis as $var)
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded mr-1 mb-1">
                                            ___{{ $var }}___
                                        </span>
                                    @empty
                                        <span class="text-gray-400 text-xs">Nenhuma</span>
                                    @endforelse
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('comfy-queue.job-models.edit', $modelo->id) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                                    <form action="{{ route('comfy-queue.job-models.destroy', $modelo->id) }}" 
                                          method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Tem certeza?')">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    Nenhum modelo encontrado. <a href="{{ route('comfy-queue.job-models.create') }}" class="text-indigo-600 hover:underline">Crie um modelo</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>