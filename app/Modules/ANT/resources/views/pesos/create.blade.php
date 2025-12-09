<x-ANT::layout>
    <x-slot name="header">

        <a href="{{ route('ant.professor.index') }}" class="text-gray-500 hover:text-gray-900">Dashboard</a>
        <span class="text-gray-400 mx-2">/</span>
        Configurar Pesos e Notas

    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="md:col-span-1">
                    <div class="bg-white shadow sm:rounded-lg p-6 sticky top-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Novo Grupo de Notas</h3>

                        <form action="{{ route('ant.pesos.store') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                                <select name="materia_id" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($materias as $materia)
                                        <option value="{{ $materia->id }}">{{ $materia->nome }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Grupo</label>
                                <input type="text" name="grupo" placeholder="Ex: P1, Trabalhos, Projeto" required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Identificador que aparecerá no boletim.</p>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valor Total (Peso)</label>
                                <input type="number" name="valor" step="0.5" min="0" max="100" placeholder="Ex: 10.0"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-bold text-lg">
                            </div>

                            <button type="submit"
                                    class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 transition">
                                Adicionar Peso
                            </button>
                        </form>

                        @if($errors->any())
                            <div class="mt-4 bg-red-50 text-red-700 p-3 rounded text-sm">
                                <ul class="list-disc pl-4">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="mt-4 bg-green-50 text-green-700 p-3 rounded text-sm text-center">
                                {{ session('success') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="md:col-span-2 space-y-6">
                    @forelse($materias as $materia)
                        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                            <div
                                class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h4 class="font-bold text-gray-800">{{ $materia->nome }}</h4>
                                <span
                                    class="text-xs font-mono bg-white border px-2 py-1 rounded">{{ $materia->nome_curto }}</span>
                            </div>

                            <div class="p-6">
                                @if(isset($pesosExistentes[$materia->id]) && $pesosExistentes[$materia->id]->isNotEmpty())
                                    <table class="min-w-full">
                                        <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <th class="pb-2">Grupo</th>
                                            <th class="pb-2">Valor</th>
                                            <th class="pb-2 text-right">Ação</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                        @php $soma = 0; @endphp
                                        @foreach($pesosExistentes[$materia->id] as $peso)
                                            @php $soma += $peso->valor; @endphp
                                            <tr>
                                                <td class="py-3 text-sm font-medium text-gray-900">{{ $peso->grupo }}</td>
                                                <td class="py-3 text-sm text-gray-600">{{ number_format($peso->valor, 1) }}</td>
                                                <td class="py-3 text-right">
                                                    <form action="{{ route('ant.pesos.destroy', $peso->id) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('Tem certeza? Isso pode afetar trabalhos já criados.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="text-red-500 hover:text-red-700 text-xs font-bold uppercase">
                                                            Excluir
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach

                                        <tr class="bg-gray-50">
                                            <td class="py-2 pl-2 text-sm font-bold text-gray-700">Total Distribuído:
                                            </td>
                                            <td class="py-2 text-sm font-bold {{ $soma > 100 ? 'text-red-600' : ($soma == 100 ? 'text-green-600' : 'text-blue-600') }}">
                                                {{ number_format($soma, 1) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    @if($soma != 10 && $soma != 100)
                                        <p class="text-xs text-yellow-600 mt-2">⚠️ Atenção: A soma dos pesos geralmente
                                            deve ser 10 ou 100.</p>
                                    @endif

                                @else
                                    <p class="text-gray-400 italic text-sm text-center py-4">Nenhum grupo de notas
                                        definido ainda.</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="bg-yellow-50 p-4 rounded text-yellow-700">Nenhuma matéria encontrada.</div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</x-ANT::layout>
