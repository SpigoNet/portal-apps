<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Editar Tarefa') }}
            </h2>

            <div class="flex space-x-2">
                <a href="{{ route('treetask.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline px-2">
                    Projetos
                </a>

                <span class="text-gray-300">|</span>

                <a href="{{ route('treetask.focus.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900 font-bold px-2">
                    Modo Zen 🧘
                </a>

                @if(request('origin') === 'focus')
                    <a href="{{ route('treetask.focus.index') }}" class="ml-4 bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded text-sm font-bold">
                        ⬅ Voltar
                    </a>
                @else
                    <a href="{{ route('treetask.show', $tarefa->fase->id_projeto) }}" class="ml-4 bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded text-sm font-bold">
                        ⬅ Voltar
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('treetask.tarefas.update', $tarefa->id_tarefa) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="origin" value="{{ request('origin') }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-gray-50 p-4 rounded border border-gray-200">

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Fase (Coluna):</label>
                                <select name="id_fase" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($fases as $fase)
                                        <option value="{{ $fase->id_fase }}" {{ $tarefa->id_fase == $fase->id_fase ? 'selected' : '' }}>
                                            {{ $fase->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Status da Tarefa:</label>
                                <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold text-gray-700">
                                    <option value="A Fazer" {{ $tarefa->status == 'A Fazer' ? 'selected' : '' }}>⚪ A Fazer</option>
                                    <option value="Planejamento" {{ $tarefa->status == 'Planejamento' ? 'selected' : '' }}>🟣 Planejamento</option>
                                    <option value="Em Andamento" {{ $tarefa->status == 'Em Andamento' ? 'selected' : '' }}>🔵 Em Andamento</option>
                                    <option value="Aguardando resposta" {{ $tarefa->status == 'Aguardando resposta' ? 'selected' : '' }}>🟡 Aguardando resposta</option>
                                    <option value="Concluído" {{ $tarefa->status == 'Concluído' ? 'selected' : '' }}>🟢 Concluído</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Título *</label>
                            <input type="text" name="titulo" value="{{ old('titulo', $tarefa->titulo) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
                            <textarea name="descricao" rows="5" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('descricao', $tarefa->descricao) }}</textarea>
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
                                <label class="block text-gray-700 text-sm font-bold mb-2">Estimativa (h)</label>
                                <input type="number" step="0.5" name="estimativa_tempo" value="{{ old('estimativa_tempo', $tarefa->estimativa_tempo) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded shadow-sm hover:bg-gray-50">Cancelar</a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold rounded shadow hover:bg-blue-700">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
