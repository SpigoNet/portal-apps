<x-ANT::layout>
    <x-slot name="header">
        {{ isset($materia) ? 'Editar Matéria' : 'Nova Matéria' }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form
                    action="{{ isset($materia) ? route('ant.admin.materias.update', $materia->id) : route('ant.admin.materias.store') }}"
                    method="POST">
                    @csrf
                    @if(isset($materia))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nome da Disciplina</label>
                        <input type="text" name="nome" value="{{ $materia->nome ?? old('nome') }}" required
                               placeholder="Ex: Laboratório de Banco de Dados"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('nome') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Nome Curto / Sigla</label>
                        <input type="text" name="nome_curto" value="{{ $materia->nome_curto ?? old('nome_curto') }}"
                               required placeholder="Ex: LBD"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                        <p class="text-xs text-gray-500 mt-1">Usado para identificar a matéria em URLs e pastas.</p>
                        @error('nome_curto') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end">
                        <a href="{{ route('ant.admin.materias.index') }}"
                           class="bg-gray-200 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            Salvar
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-ANT::layout>
