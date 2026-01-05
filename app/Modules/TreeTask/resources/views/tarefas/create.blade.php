<x-TreeTask::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gerenciar Tarefa') }}
            </h2>

            @if(request('origin') === 'focus')
                <a href="{{ route('treetask.focus.index') }}" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded inline-flex items-center">
                    ⬅ Voltar ao Foco
                </a>
            @elseif(isset($tarefa))
                <a href="{{ route('treetask.show', $tarefa->fase->id_projeto) }}" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded inline-flex items-center">
                    ⬅ Voltar ao Projeto
                </a>
            @else
                <a href="{{ url()->previous() }}" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded inline-flex items-center">
                    ⬅ Voltar
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('treetask.tarefas.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id_fase" value="{{ $fase->id_fase }}">

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Título da Tarefa *</label>
                            <input type="text" name="titulo" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
                            <textarea name="descricao" rows="4" class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Responsável *</label>
                                <select name="id_user_responsavel" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="">Selecione...</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Prioridade</label>
                                <select name="prioridade" class="w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="Baixa">Baixa</option>
                                    <option value="Média" selected>Média</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Urgente">Urgente</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Vencimento</label>
                                <input type="date" name="data_vencimento" class="w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Estimativa (horas)</label>
                                <input type="number" step="0.5" name="estimativa_tempo" class="w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('treetask.show', $fase->id_projeto) }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancelar</a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Criar Tarefa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-TreeTask::layout>
