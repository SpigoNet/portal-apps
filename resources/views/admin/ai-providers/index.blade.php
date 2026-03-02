<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Provedores de IA</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-end mb-4">
                        <a href="{{ route('admin.ai-providers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            + Novo Provedor
                        </a>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entrada</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saída</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($providers as $provider)
                                <tr>
                                    <td class="px-6 py-4">{{ $provider->name }}</td>
                                    <td class="px-6 py-4">{{ $provider->driver }}</td>
                                    <td class="px-6 py-4">{{ $provider->input_type === 'text' ? 'Texto' : 'Imagem' }}</td>
                                    <td class="px-6 py-4">
                                        @switch($provider->output_type)
                                            @case('text') Texto @break
                                            @case('image') Imagem @break
                                            @case('audio') Áudio @break
                                            @case('video') Vídeo @break
                                        @endswitch
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($provider->is_active)
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Ativo</span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Inativo</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.ai-models.index', ['provider_id' => $provider->id]) }}" class="text-green-600 hover:text-green-900 mr-3">
                                            <i class="fa-solid fa-cubes"></i> Modelos
                                        </a>
                                        @if($provider->sync_url)
                                            <form action="{{ route('admin.ai-providers.sync', $provider->id) }}" method="POST" class="inline mr-3">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-900" onclick="this.disabled=true;this.form.submit();">
                                                    <i class="fa-solid fa-sync"></i> Sync
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.ai-providers.edit', $provider->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                                        <form action="{{ route('admin.ai-providers.destroy', $provider->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza?')">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum provedor cadastrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
