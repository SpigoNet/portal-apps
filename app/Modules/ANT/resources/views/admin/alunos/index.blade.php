<x-ANT::layout>
    <x-slot name="header">
        Gerenciar Matrículas
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div
                    class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                <form action="{{ route('ant.admin.alunos.index') }}" method="GET"
                      class="flex flex-col md:flex-row gap-4 items-end">

                    <div class="w-full md:w-1/4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                        <input type="text" name="semestre" value="{{ $filtroSemestre }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="w-full md:w-1/2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                        <select name="materia_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione uma disciplina...</option>
                            @foreach($materias as $materia)
                                <option
                                    value="{{ $materia->id }}" {{ $filtroMateria == $materia->id ? 'selected' : '' }}>
                                    {{ $materia->nome }} ({{ $materia->nome_curto }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full md:w-auto">
                        <button type="submit"
                                class="w-full bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 shadow font-bold">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($filtroMateria)
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <span class="font-bold text-gray-700">Alunos Matriculados: {{ $alunos->count() }}</span>
                        <a href="{{ route('ant.admin.alunos.importar') }}"
                           class="text-sm text-indigo-600 hover:underline">Importar mais alunos</a>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                RA
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nome
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Data Matrícula
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($alunos as $aluno)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $aluno->ra }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $aluno->nome }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($aluno->created_at)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form action="{{ route('ant.admin.alunos.destroy', $aluno->matricula_id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Tem certeza? O aluno será removido desta disciplina.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900 font-bold bg-red-50 px-3 py-1 rounded hover:bg-red-100 transition">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">
                                    Nenhum aluno encontrado nesta disciplina/semestre.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @else
                    <div class="p-10 text-center text-gray-500">
                        <span class="material-icons text-4xl mb-2 text-gray-300">filter_list</span>
                        <p>Selecione uma disciplina acima para ver a lista de alunos.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</x-ANT::layout>
