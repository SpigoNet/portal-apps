<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Provedor de IA</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('mundos-de-mim.admin.ai-providers.update', $provider->id) }}" method="POST" class="grid grid-cols-1 gap-6">
                    @csrf @method('PUT')
                    <div><label class="block text-sm font-medium text-gray-700">Nome</label><input type="text" name="name" value="{{ old('name', $provider->name) }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Driver</label><input type="text" name="driver" value="{{ old('driver', $provider->driver) }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Base URL</label><input type="url" name="base_url" value="{{ old('base_url', $provider->base_url) }}" class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">URL de atualização (sync)</label><input type="url" name="sync_url" value="{{ old('sync_url', $provider->sync_url) }}" class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">API Key</label><input type="password" name="api_key" value="{{ old('api_key') }}" placeholder="Deixe em branco para manter a chave atual" class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <label class="flex items-center"><input type="checkbox" name="is_active" {{ $provider->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm text-gray-700">Ativo</span></label>
                    <div class="flex justify-end gap-3"><a href="{{ route('mundos-de-mim.admin.ai-providers.index') }}" class="px-4 py-2 bg-gray-200 rounded">Cancelar</a><button class="px-4 py-2 bg-indigo-600 text-white rounded">Salvar</button></div>
                </form>
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
