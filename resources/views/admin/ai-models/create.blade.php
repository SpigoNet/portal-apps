<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.ai-models.index', $selectedProviderId ? ['provider_id' => $selectedProviderId] : []) }}" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-arrow-left"></i> Modelos
            </a>
            <span class="text-gray-400">/</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Modelo de IA</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.ai-models.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provedor</label>
                            <select name="provider_id" class="w-full rounded border-gray-300" required>
                                <option value="">Selecione...</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ (old('provider_id', $selectedProviderId) == $provider->id) ? 'selected' : '' }}>
                                        {{ $provider->name }} ({{ $provider->driver }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded border-gray-300" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Model ID</label>
                            <input type="text" name="model" value="{{ old('model') }}" placeholder="ex: nano-banana-2" class="w-full rounded border-gray-300 font-mono" required>
                            <p class="text-xs text-gray-500 mt-1">Identificador técnico do modelo na API do provedor</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                            <textarea name="description" rows="3" class="w-full rounded border-gray-300">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ordem</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="w-full rounded border-gray-300">
                        </div>

                        <div class="mb-4 space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="supports_image_input" value="1" {{ old('supports_image_input') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                <span class="ml-2 text-sm text-gray-700">Suporta entrada de imagem</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="supports_video_output" value="1" {{ old('supports_video_output') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                <span class="ml-2 text-sm text-gray-700">Suporta saída de vídeo</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                <span class="ml-2 text-sm text-gray-700">Modelo padrão</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                <span class="ml-2 text-sm text-gray-700">Ativo</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.ai-models.index', $selectedProviderId ? ['provider_id' => $selectedProviderId] : []) }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
