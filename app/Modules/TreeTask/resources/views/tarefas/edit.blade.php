<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Tarefa: {{ $tarefa->titulo }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('treetask.tarefas.update', $tarefa->id_tarefa) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6 bg-blue-50 p-4 rounded border border-blue-200">
                            <label class="block text-blue-800 text-sm font-bold mb-2">Mover para Fase:</label>
                            <select name="id_fase" class="w-full border-blue-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @foreach($fases as $fase)
                                    <option value="{{ $fase->id_fase }}" {{ $tarefa->id_fase == $fase->id_fase ? 'selected' : '' }}>
                                        {{ $fase->nome }} ({{ $fase->status }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-blue-600 mt-1">Alterar a fase moverá o card no quadro automaticamente.</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Título da Tarefa *</label>
                            <input type="text" name="titulo" value="{{ old('titulo', $tarefa->titulo) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
                            <textarea name="descricao" rows="4" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('descricao', $tarefa->descricao) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Responsável *</label>
                                <select name="id_user_responsavel" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $tarefa->id_user_responsavel == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Prioridade</label>
                                <select name="prioridade" class="w-full border-gray-300 rounded-md shadow-sm">
                                    @foreach(['Baixa', 'Média', 'Alta', 'Urgente'] as $prio)
                                        <option value="{{ $prio }}" {{ $tarefa->prioridade == $prio ? 'selected' : '' }}>{{ $prio }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Vencimento</label>
                                <input type="date" name="data_vencimento"
                                       value="{{ $tarefa->data_vencimento ? \Carbon\Carbon::parse($tarefa->data_vencimento)->format('Y-m-d') : '' }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Estimativa (horas)</label>
                                <input type="number" step="0.5" name="estimativa_tempo" value="{{ old('estimativa_tempo', $tarefa->estimativa_tempo) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('treetask.show', $tarefa->fase->id_projeto) }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancelar</a>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
