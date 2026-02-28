<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Provedores de IA</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between mb-6 gap-2">
                <div class="flex gap-2">
                    <a href="{{ route('mundos-de-mim.admin.ai-providers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700">+ Novo Provedor</a>
                    <a href="{{ route('mundos-de-mim.admin.ai-models.index') }}" class="bg-gray-700 text-white px-4 py-2 rounded shadow hover:bg-gray-800">Ver Modelos</a>
                </div>
                <div class="flex gap-2">
                    <form action="{{ route('mundos-de-mim.admin.ai-providers.sync-pollination') }}" method="POST">@csrf
                        <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded shadow hover:bg-orange-700">Sync Pollination</button>
                    </form>
                    <form action="{{ route('mundos-de-mim.admin.ai-providers.sync-airforce') }}" method="POST">@csrf
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Sync AirForce</button>
                    </form>
                </div>
            </div>

            @if(session('success'))<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>@endif

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Base URL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sync URL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">API Key</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($providers as $provider)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $provider->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $provider->driver }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $provider->base_url ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $provider->sync_url ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $provider->api_key ? 'Configurada' : '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $provider->is_active ? 'Ativo' : 'Inativo' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('mundos-de-mim.admin.ai-providers.edit', $provider->id) }}" class="text-indigo-600 hover:text-indigo-900 text-xs">Editar</a>
                                    <form action="{{ route('mundos-de-mim.admin.ai-providers.destroy', $provider->id) }}" method="POST" class="inline">@csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-xs ml-3" onclick="return confirm('Excluir provedor?')">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Nenhum provedor cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
