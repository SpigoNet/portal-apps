<x-MundosDeMim::layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Modelo de IA</h2></x-slot>
    <div class="py-12"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white p-6 shadow-sm sm:rounded-lg">
        <form action="{{ route('mundos-de-mim.admin.ai-models.update', $model->id) }}" method="POST" class="grid grid-cols-1 gap-6">
            @csrf @method('PUT')
            <div><label class="block text-sm font-medium text-gray-700">Provedor Pai</label><select name="provider_id" required class="mt-1 block w-full rounded-md border-gray-300">@foreach($providers as $provider)<option value="{{ $provider->id }}" {{ (string) old('provider_id', $model->provider_id) === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }} ({{ $provider->driver }})</option>@endforeach</select></div>
            <div><label class="block text-sm font-medium text-gray-700">Nome</label><input type="text" name="name" value="{{ old('name', $model->name) }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
            <div><label class="block text-sm font-medium text-gray-700">Model</label><input type="text" name="model" value="{{ old('model', $model->model) }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
            <div><label class="block text-sm font-medium text-gray-700">Descrição</label><textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300">{{ old('description', $model->description) }}</textarea></div>
            <div><label class="block text-sm font-medium text-gray-700">Ordem</label><input type="number" name="sort_order" value="{{ old('sort_order', $model->sort_order) }}" class="mt-1 block w-full rounded-md border-gray-300"></div>
            <div class="flex gap-6"><label class="flex items-center"><input type="checkbox" name="supports_image_input" {{ $model->supports_image_input ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">Entrada de imagem</span></label><label class="flex items-center"><input type="checkbox" name="supports_video_output" {{ $model->supports_video_output ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">Saída de vídeo</span></label></div>
            <div class="flex gap-6"><label class="flex items-center"><input type="checkbox" name="is_default" {{ $model->is_default ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">Padrão</span></label><label class="flex items-center"><input type="checkbox" name="is_active" {{ $model->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600"><span class="ml-2 text-sm">Ativo</span></label></div>
            <div class="mt-2 flex justify-end gap-3"><a href="{{ route('mundos-de-mim.admin.ai-models.index') }}" class="px-4 py-2 bg-gray-200 rounded">Cancelar</a><button class="px-4 py-2 bg-indigo-600 text-white rounded">Salvar</button></div>
        </form>
    </div></div></div>
</x-MundosDeMim::layout>
