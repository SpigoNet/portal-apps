<x-ANT::layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800">Histórico de trabalhos</h2>
            <a href="{{ route('ant.professor.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">Painel do Professor</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-600">Consulte trabalhos e entregas de semestres anteriores. Esta consulta não altera o semestre atual ({{ $semestreAtual }}) nem o semestre selecionado no painel.</p>

            @if($semestres->isNotEmpty())
                <form method="GET" action="{{ route('ant.professor.historico') }}" class="flex flex-wrap items-end gap-3 bg-white p-4 shadow-sm rounded-lg">
                    <div>
                        <label for="semestre" class="block text-sm font-medium text-gray-700 mb-1">Semestre do histórico</label>
                        <select name="semestre" id="semestre" class="rounded-md border-gray-300 text-sm">
                            @foreach($semestres as $semestre)
                                <option value="{{ $semestre }}" @selected($semestre === $semestreSelecionado)>{{ $semestre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="trabalho" class="block text-sm font-medium text-gray-700 mb-1">Nome do trabalho</label>
                        <input type="search" id="trabalho" name="trabalho" value="{{ $filtros['trabalho'] }}" maxlength="255" class="w-full rounded-md border-gray-300 text-sm" placeholder="Parte do nome">
                        @error('trabalho')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="disciplina" class="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                        <input type="search" id="disciplina" name="disciplina" value="{{ $filtros['disciplina'] }}" maxlength="255" class="w-full rounded-md border-gray-300 text-sm" placeholder="Nome da disciplina">
                        @error('disciplina')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="aluno" class="block text-sm font-medium text-gray-700 mb-1">Aluno que entregou</label>
                        <input type="search" id="aluno" name="aluno" value="{{ $filtros['aluno'] }}" maxlength="255" class="w-full rounded-md border-gray-300 text-sm" placeholder="Nome ou RA">
                        @error('aluno')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <a href="{{ route('ant.professor.historico', ['semestre' => $semestreSelecionado]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Limpar pesquisa</a>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">Consultar</button>
                    @error('semestre')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </form>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                @if($trabalhos->isEmpty())
                    <p class="p-6 text-gray-500">Nenhum trabalho encontrado para os filtros selecionados.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Trabalho / Disciplina</th>
                                    <th scope="col" class="px-6 py-3">Semestre</th>
                                    <th scope="col" class="px-6 py-3">Prazo</th>
                                    <th scope="col" class="px-6 py-3">Entregas</th>
                                    <th scope="col" class="px-6 py-3">Consulta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-sm">
                                @foreach($trabalhos as $trabalho)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-900">{{ $trabalho->nome }}</div>
                                            <div class="text-gray-500">{{ $trabalho->materia->nome }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">{{ $trabalho->semestre }}</td>
                                        <td class="px-6 py-4 text-gray-600">{{ $trabalho->prazo?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-6 py-4 text-gray-600">{{ $trabalho->entregas_count }}</td>
                                        <td class="px-6 py-4"><a href="{{ route('ant.professor.trabalho', $trabalho->id) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">Ver trabalho e entregas</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $trabalhos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-ANT::layout>
