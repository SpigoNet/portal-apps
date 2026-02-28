<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Provedor de IA
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('mundos-de-mim.admin.ai-providers.update', $provider->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" name="name" value="{{ old('name', $provider->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Driver</label>
                            <input type="text" name="driver" value="{{ old('driver', $provider->driver) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">API Key</label>
                            <input type="text" name="api_key" value="{{ old('api_key', $provider->api_key) }}" placeholder="{{ $provider->api_key ? '•••••••• configurada' : 'sk-...' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-sm text-gray-500">Deixe em branco para manter sem chave.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model</label>
                            <input type="text" name="model" value="{{ old('model', $provider->model) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição</label>
                            <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $provider->description) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ordem de Exibição</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $provider->sort_order) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex gap-6">
                            <div class="flex items-center">
                                <input type="checkbox" name="supports_image_input" id="supports_image_input" {{ $provider->supports_image_input ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <label for="supports_image_input" class="ml-2 block text-sm text-gray-700">Suporta Entrada de Imagem</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="supports_video_output" id="supports_video_output" {{ $provider->supports_video_output ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <label for="supports_video_output" class="ml-2 block text-sm text-gray-700">Gera Vídeo</label>
                            </div>
                        </div>

                        <div class="flex gap-6">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_default" id="is_default" {{ $provider->is_default ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <label for="is_default" class="ml-2 block text-sm text-gray-700">Provedor Padrão</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" {{ $provider->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <label for="is_active" class="ml-2 block text-sm text-gray-700">Ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-4">
                        <a href="{{ route('mundos-de-mim.admin.ai-providers.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            Atualizar Provedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
