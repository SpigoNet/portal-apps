<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Adicionar Ente Querido') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-medium text-gray-900">Nova Pessoa para Artes em Dupla</h3>
                        <p class="text-sm text-gray-500">
                            Adicione alguém especial para aparecer junto com você nos cenários gerados.
                            As fotos seguem o padrão de segurança e isolamento de dados.
                        </p>
                    </div>

                    <form action="{{ route('mundos-de-mim.pessoas.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="grid grid-cols-1 gap-6">

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Nome da Pessoa</label>
                                <input type="text" name="name" id="name" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="Ex: Maria, João, Rex">
                            </div>

                            <div>
                                <label for="relationship" class="block text-sm font-medium text-gray-700">Relacionamento com você</label>
                                <select name="relationship" id="relationship" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="" disabled selected>Selecione uma opção...</option>
                                    <option value="Namorado(a)">Namorado(a) / Cônjuge</option>
                                    <option value="Filho(a)">Filho(a)</option>
                                    <option value="Pai/Mãe">Pai / Mãe</option>
                                    <option value="Amigo(a)">Amigo(a)</option>
                                    <option value="Pet">Pet (Animal de Estimação)</option>
                                    <option value="Outro">Outro</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Isso ajuda a IA a entender a interação na imagem (ex: romântica vs. familiar).</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Foto de Rosto</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="photo" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>Carregar um arquivo</span>
                                                <input id="photo" name="photo" type="file" class="sr-only" accept="image/*" required>
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG até 5MB</p>
                                    </div>
                                </div>
                                @error('photo')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>

                        <div class="mt-6 flex items-center justify-end gap-x-4">
                            <a href="{{ route('mundos-de-mim.pessoas.index') }}" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</a>
                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Salvar Pessoa
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
