<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.ai-providers.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-arrow-left"></i> Provedores
            </a>
            <span class="text-gray-400">/</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Modelos de IA
                @if($currentProvider)
                    — {{ $currentProvider->name }}
                @endif
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <form method="GET" class="flex gap-2 items-center">
                            @if($currentProvider)
                                <input type="hidden" name="provider_id" value="{{ $currentProvider->id }}">
                                <span class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded">
                                    <i class="fa-solid fa-lock text-xs mr-1"></i>{{ $currentProvider->name }} ({{ $currentProvider->driver }})
                                </span>
                            @endif
                            <input type="text" name="search" placeholder="Buscar..." value="{{ request('search') }}" class="rounded border-gray-300 text-sm">
                            <select name="status" class="rounded border-gray-300 text-sm">
                                <option value="">Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Ativo</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inativo</option>
                            </select>
                            <button type="submit" class="bg-gray-200 px-3 py-1 rounded text-sm">Filtrar</button>
                        </form>
                        <div class="flex gap-2">
                            @if($currentProvider && $currentProvider->sync_url)
                                <form action="{{ route('admin.ai-models.sync', $currentProvider->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm" onclick="this.disabled=true;this.form.submit();">
                                        <i class="fa-solid fa-sync mr-1"></i> Sincronizar
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.ai-models.create', $currentProvider ? ['provider_id' => $currentProvider->id] : []) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm">
                                + Novo Modelo
                            </a>
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model ID</th>
                                @if(!$currentProvider)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provedor</th>
                                @endif
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Imagem</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vídeo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Padrão</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($models as $model)
                                <tr>
                                    <td class="px-6 py-4">{{ $model->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $model->model }}</td>
                                    @if(!$currentProvider)
                                        <td class="px-6 py-4 text-sm">{{ $model->provider?->name ?? $model->driver }}</td>
                                    @endif
                                    <td class="px-6 py-4 text-center">
                                        @if($model->supports_image_input)
                                            <span class="text-green-600"><i class="fa-solid fa-check"></i></span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($model->supports_video_output)
                                            <span class="text-green-600"><i class="fa-solid fa-check"></i></span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($model->is_default)
                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Padrão</span>
                                        @endif
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
                                    <td colspan="{{ $currentProvider ? 7 : 8 }}" class="px-6 py-4 text-center text-gray-500">Nenhum modelo cadastrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
