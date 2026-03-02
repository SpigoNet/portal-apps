<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Provedor de IA</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.ai-providers.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded border-gray-300" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                            <input type="text" name="driver" value="{{ old('driver') }}" placeholder="ex: pollination, airforce, gemini" class="w-full rounded border-gray-300" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Entrada</label>
                                <select name="input_type" class="w-full rounded border-gray-300" required>
                                    <option value="text" {{ old('input_type') === 'text' ? 'selected' : '' }}>Texto</option>
                                    <option value="image" {{ old('input_type', 'image') === 'image' ? 'selected' : '' }}>Imagem</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Saída</label>
                                <select name="output_type" class="w-full rounded border-gray-300" required>
                                    <option value="text" {{ old('output_type') === 'text' ? 'selected' : '' }}>Texto</option>
                                    <option value="image" {{ old('output_type', 'image') === 'image' ? 'selected' : '' }}>Imagem</option>
                                    <option value="audio" {{ old('output_type') === 'audio' ? 'selected' : '' }}>Áudio</option>
                                    <option value="video" {{ old('output_type') === 'video' ? 'selected' : '' }}>Vídeo</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Base URL (opcional)</label>
                            <input type="url" name="base_url" value="{{ old('base_url') }}" placeholder="https://api.exemplo.com" class="w-full rounded border-gray-300">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sync URL (para buscar modelos)</label>
                            <input type="url" name="sync_url" value="{{ old('sync_url') }}" placeholder="https://api.exemplo.com/models" class="w-full rounded border-gray-300">
                            <p class="text-xs text-gray-500 mt-1">URL para sincronizar lista de modelos disponíveis</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                            <input type="password" name="api_key" value="{{ old('api_key') }}" class="w-full rounded border-gray-300">
                            <p class="text-xs text-gray-500 mt-1">Deixe em branco para manter a chave atual</p>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                <span class="ml-2 text-sm text-gray-700">Ativo</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.ai-providers.index') }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
