<x-ANT::layout>
    <x-slot name="header">
        Gerenciar Matérias
        <span class="ml-4 pl-4 border-l border-white/20"></span>
        <a href="{{ route('ant.admin.materias.create') }}"
           class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            + Nova Matéria
        </a>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div
                    class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div
                    class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sigla / Nome Curto
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($materias as $materia)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $materia->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="bg-gray-100 text-gray-800 text-xs font-mono px-2 py-1 rounded">{{ $materia->nome_curto }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('ant.admin.materias.edit', $materia->id) }}"
                                   class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>

                                <form action="{{ route('ant.admin.materias.destroy', $materia->id) }}" method="POST"
                                      class="inline-block" onsubmit="return confirm('Tem certeza?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if($materias->isEmpty())
                    <div class="p-6 text-center text-gray-500">Nenhuma matéria cadastrada.</div>
                @endif
            </div>
        </div>
    </div>
</x-ANT::layout>
