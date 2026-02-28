<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modelos de IA</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between mb-6 gap-2">
                <div class="flex gap-2">
                    <a href="{{ route('mundos-de-mim.admin.ai-models.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700">+ Novo Modelo</a>
                    <a href="{{ route('mundos-de-mim.admin.ai-providers.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded shadow hover:bg-gray-800">Ver Provedores</a>
                </div>
                <a href="{{ route('mundos-de-mim.admin.ai-models.user-settings') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Configuração por Usuário</a>
            </div>

            @if(session('success'))<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>@endif

            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="min-w-[220px]"><label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label><input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-md border-gray-300" placeholder="Nome, model, descrição"></div>
                    <div class="min-w-[220px]"><label class="block text-sm font-medium text-gray-700 mb-1">Provedor</label><select name="provider_id" class="w-full rounded-md border-gray-300"><option value="">Todos</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" {{ (string) request('provider_id') === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>@endforeach</select></div>
                    <button class="bg-gray-600 text-white px-4 py-2 rounded">Filtrar</button>
                    <a href="{{ route('mundos-de-mim.admin.ai-models.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Limpar</a>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($models as $model)
                    <div class="bg-white rounded-lg shadow p-4 {{ $model->is_default ? 'ring-2 ring-yellow-400' : '' }} {{ !$model->is_active ? 'opacity-60' : '' }}">
                        <div class="flex justify-between mb-2">
                            <div>
                                <h3 class="font-bold text-lg">{{ $model->name }}</h3>
                                <div class="text-xs text-gray-500">{{ $model->gatewayProvider?->name ?? 'Sem Provedor' }} / {{ $model->driver }}</div>
                                <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">{{ $model->model }}</code>
                            </div>
                            <div class="text-xs">{{ $model->is_active ? 'Ativo' : 'Inativo' }}</div>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">{{ $model->description }}</p>
                        <div class="flex justify-between items-center pt-2 border-t text-xs">
                            <div>Ordem: {{ $model->sort_order }}</div>
                            <div class="flex gap-2">
                                @if(!$model->is_default)
                                    <form action="{{ route('mundos-de-mim.admin.ai-models.set-default', $model->id) }}" method="POST" class="inline">@csrf<button type="submit" class="text-blue-600">Padrão</button></form>
                                @endif
                                <a href="{{ route('mundos-de-mim.admin.ai-models.edit', $model->id) }}" class="text-indigo-600">Editar</a>
                                <form action="{{ route('mundos-de-mim.admin.ai-models.destroy', $model->id) }}" method="POST" class="inline">@csrf @method('DELETE')<button type="submit" class="text-red-600" onclick="return confirm('Excluir modelo?')">Excluir</button></form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500">Nenhum modelo encontrado.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
