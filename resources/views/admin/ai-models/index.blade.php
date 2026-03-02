<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modelos de IA</h2>
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
                    <div class="flex justify-between items-center mb-4">
                        <form method="GET" class="flex gap-2">
                            <input type="text" name="search" placeholder="Buscar..." value="{{ request('search') }}" class="rounded border-gray-300 text-sm">
                            <select name="input_type" class="rounded border-gray-300 text-sm">
                                <option value="">Entrada</option>
                                <option value="text" {{ request('input_type') == 'text' ? 'selected' : '' }}>Texto</option>
                                <option value="image" {{ request('input_type') == 'image' ? 'selected' : '' }}>Imagem</option>
                            </select>
                            <select name="output_type" class="rounded border-gray-300 text-sm">
                                <option value="">Saída</option>
                                <option value="text" {{ request('output_type') == 'text' ? 'selected' : '' }}>Texto</option>
                                <option value="image" {{ request('output_type') == 'image' ? 'selected' : '' }}>Imagem</option>
                                <option value="audio" {{ request('output_type') == 'audio' ? 'selected' : '' }}>Áudio</option>
                                <option value="video" {{ request('output_type') == 'video' ? 'selected' : '' }}>Vídeo</option>
                            </select>
                            <button type="submit" class="bg-gray-200 px-3 py-1 rounded text-sm">Filtrar</button>
                        </form>
                        <a href="{{ route('admin.ai-models.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            + Novo Modelo
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
                            @forelse($models as $model)
                                <tr>
                                    <td class="px-6 py-4">{{ $model->name }}</td>
                                    <td class="px-6 py-4">{{ $model->driver }}</td>
                                    <td class="px-6 py-4">{{ $model->input_type === 'text' ? 'Texto' : 'Imagem' }}</td>
                                    <td class="px-6 py-4">
                                        @switch($model->output_type)
                                            @case('text') Texto @break
                                            @case('image') Imagem @break
                                            @case('audio') Áudio @break
                                            @case('video') Vídeo @break
                                        @endswitch
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($model->is_active)
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Ativo</span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Inativo</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.ai-models.edit', $model->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                                        <form action="{{ route('admin.ai-models.destroy', $model->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza?')">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum modelo cadastrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
