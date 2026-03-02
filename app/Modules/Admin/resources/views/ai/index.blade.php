<x-Admin::layout>


    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 ">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Gestão de IA: Provedores e Modelos
                </h2>
                <a href="{{ route('admin.ai.provedores.create') }}"
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Novo Provedor
                </a>
            </div>
        </div>

        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="w-full text-left">
                        <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="py-2">Provedor</th>
                            <th class="py-2">Modelos</th>
                            <th class="py-2">Última Sinc.</th>
                            <th class="py-2 text-right">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($provedores as $p)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="py-3">
                                    <div class="font-bold">{{ $p->nome }}</div>
                                    <div class="text-xs text-gray-500">{{ $p->url_json_modelos }}</div>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                        {{ $p->modelos_count }} modelos
                                    </span>
                                </td>
                                <td class="py-3 text-sm">
                                    {{ $p->updated_at->diffForHumans() }}
                                </td>
                                <td class="py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.ai.modelos.index', $p) }}"
                                           class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded transition">
                                            <i class="fa-solid fa-list"></i> Modelos
                                        </a>

                                        <a href="{{ route('admin.ai.provedores.edit', $p) }}"
                                           class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition">
                                            <i class="fa-solid fa-edit"></i> Editar
                                        </a>

                                        <form action="{{ route('admin.ai.provedores.sync', $p) }}" method="POST"
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="text-sm bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded transition">
                                                <i class="fa-solid fa-sync"></i> Sincronizar
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.ai.provedores.destroy', $p) }}" method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Tem certeza que deseja remover este provedor e todos os seus modelos?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition">
                                                <i class="fa-solid fa-trash"></i> Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($modelosPadrao->count() > 0)
                <div class="mt-8 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">Modelos Padrão por Capacidade</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($modelosPadrao as $mp)
                                <div class="border dark:border-gray-700 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            {{ $mp->input_type }} <i class="fa-solid fa-arrow-right mx-1 text-[10px]"></i> {{ $mp->output_type }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ $mp->modelo->nome }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Provedor: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $mp->modelo->provedor->nome }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-Admin::layout>
