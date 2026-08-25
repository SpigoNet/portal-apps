<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.professor.apresentacoes.index') }}" class="text-gray-500 hover:text-gray-900">Apresentações</a>
            <span class="text-gray-400 mx-2">/</span>
            Nova Apresentação
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('ant.professor.apresentacoes.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Matéria</label>
                            <select name="materia_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @foreach($materias as $materia)
                                    <option value="{{ $materia->id }}">{{ $materia->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Nome da Apresentação</label>
                            <input type="text" name="nome" required maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                   value="{{ old('nome') }}">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Descrição / Enunciado</label>
                            <textarea name="descricao" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('descricao') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Peso: Nota da Apresentação
                                </label>
                                <select name="peso_apresentacao_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Selecione...</option>
                                    @foreach($pesos->groupBy('materia_id') as $materiaPesos)
                                        <optgroup label="{{ $materiaPesos->first()->materia->nome }}">
                                            @foreach($materiaPesos as $peso)
                                                <option value="{{ $peso->id }}">{{ $peso->grupo }} ({{ $peso->valor }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Peso: Participação (Estrelas)
                                </label>
                                <select name="peso_participacao_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Selecione...</option>
                                    @foreach($pesos->groupBy('materia_id') as $materiaPesos)
                                        <optgroup label="{{ $materiaPesos->first()->materia->nome }}">
                                            @foreach($materiaPesos as $peso)
                                                <option value="{{ $peso->id }}">{{ $peso->grupo }} ({{ $peso->valor }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="text-sm text-gray-500 mb-4 bg-blue-50 p-3 rounded">
                            A <strong>Nota da Apresentação</strong> é a avaliação individual de quem apresentou.<br>
                            A <strong>Participação (Estrelas)</strong> é normalizada pelo maior número de estrelas da turma no final do semestre.
                        </div>

                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold">
                            Criar Apresentação
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-ANT::layout>
