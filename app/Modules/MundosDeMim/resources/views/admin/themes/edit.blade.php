<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editando: {{ $theme->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white p-6 rounded shadow-sm">
                <form action="{{ route('mundos-de-mim.admin.themes.update', $theme->id) }}" method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div>
                            <h3 class="text-lg font-bold mb-4">Dados Principais</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                                    <input type="text" name="name" value="{{ $theme->name }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                                    <input type="text" name="slug" value="{{ $theme->slug }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Classificação</label>
                                    <select name="age_rating" class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="kids" {{ $theme->age_rating == 'kids' ? 'selected' : '' }}>Kids
                                        </option>
                                        <option value="teen" {{ $theme->age_rating == 'teen' ? 'selected' : '' }}>Teen
                                        </option>
                                        <option value="adult" {{ $theme->age_rating == 'adult' ? 'selected' : '' }}>
                                            Adult
                                        </option>
                                    </select>
                                </div>
                                <div class="flex items-center pt-2">
                                    <input type="checkbox" name="is_seasonal" value="1"
                                           {{ $theme->is_seasonal ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                    <label class="ml-2 text-sm text-gray-900">Sazonal</label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold mb-4">Mídia de Exibição</h3>

                            <div class="mb-6 p-4 bg-gray-50 rounded border">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Imagem de Referência
                                    (Antes)</label>
                                <div class="flex items-center gap-4">
                                    @if($theme->example_input_path)
                                        <img src="{{ Storage::url($theme->example_input_path) }}"
                                             class="h-16 w-16 object-cover rounded border">
                                    @else
                                        <div
                                            class="h-16 w-16 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">
                                            Sem Foto
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <input type="file" name="example_input" class="text-sm w-full">
                                        <input type="text" name="example_input_description"
                                               value="{{ $theme->example_input_description }}"
                                               placeholder="Descrição (Ex: Foto Casal)"
                                               class="mt-2 w-full text-xs rounded-md border-gray-300">
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-indigo-50 rounded border border-indigo-100">
                                <label class="block text-sm font-bold text-indigo-900 mb-2">Adicionar Resultados
                                    (Depois)</label>
                                <input type="file" name="example_outputs[]" multiple
                                       class="text-sm w-full text-indigo-600">
                                <p class="text-xs text-indigo-400 mt-1">Você pode selecionar vários arquivos.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-gray-900 text-white px-6 py-2 rounded hover:bg-gray-800">Salvar
                            Dados
                        </button>
                    </div>
                </form>
            </div>

            @if($theme->examples->isNotEmpty())
                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Galeria de Exemplos Atuais</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($theme->examples as $example)
                            <div class="relative group aspect-square rounded-lg overflow-hidden border bg-gray-100">
                                <img src="{{ Storage::url($example->image_path) }}" class="w-full h-full object-cover">

                                <form action="{{ route('mundos-de-mim.admin.themes.destroyExample', $example->id) }}"
                                      method="POST"
                                      class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700"
                                            title="Remover Imagem">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white p-6 rounded shadow-sm border-t-4 border-indigo-500">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">Prompts & Variações</h3>
                    <a href="{{ route('mundos-de-mim.admin.prompts.create', $theme->id) }}"
                       class="bg-indigo-600 text-white px-3 py-1 rounded text-sm hover:bg-indigo-700">
                        + Adicionar Prompt
                    </a>
                </div>
                <div class="space-y-4">
                    @forelse($theme->prompts as $prompt)
                        <div class="border rounded p-4 bg-gray-50 relative group hover:bg-gray-100 transition-colors">
                            <div class="pr-20"><p class="text-sm text-gray-800 italic font-medium">
                                    "{{ $prompt->prompt_text }}"</p>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse($prompt->requirements as $req)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                            {{ $req->requirement_key }} {{ $req->operator }} {{ $req->requirement_value }}
                                        </span>
                                    @empty
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                            Universal (Sem requisitos)
                                        </span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="absolute top-2 right-2 flex items-center gap-2">
                                <a href="{{ route('mundos-de-mim.admin.prompts.edit', $prompt->id) }}"
                                   class="p-1 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded"
                                   title="Editar Prompt e Requisitos">
                                    ✏️
                                </a>

                                <form action="{{ route('mundos-de-mim.admin.prompts.destroy', $prompt->id) }}"
                                      method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded"
                                            onclick="return confirm('Deletar este prompt permanentemente?')"
                                            title="Excluir">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-gray-50 rounded border border-dashed border-gray-300">
                            <p class="text-gray-500 mb-2">Nenhum prompt cadastrado para este tema.</p>
                            <a href="{{ route('mundos-de-mim.admin.prompts.create', $theme->id) }}"
                               class="text-indigo-600 text-sm font-bold hover:underline">
                                Crie o primeiro agora
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-MundosDeMim::layout>

