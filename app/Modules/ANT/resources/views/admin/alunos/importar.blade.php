<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Importar Alunos e Matrículas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    @if(session('success'))
                        <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                            <p class="font-bold">Sucesso!</p>
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                            <p class="font-bold">Erro!</p>
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                    <form action="{{ route('ant.admin.alunos.processar') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Semestre Letivo</label>
                                <input type="text" name="semestre" value="{{ $semestreAtual }}" required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50 font-bold">
                                <p class="text-xs text-gray-500 mt-1">Ex: 2025-2</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Disciplina Alvo</label>
                                <select name="materia_id" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selecione...</option>
                                    @foreach($materias as $materia)
                                        <option value="{{ $materia->id }}">{{ $materia->nome }} ({{ $materia->nome_curto }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="mb-6">

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Lista de Alunos (Copie e Cole)</label>
                            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-2 text-sm">
                                <strong>Formato Aceito:</strong> RA e Nome na mesma linha.<br>
                                Exemplo:<br>
                                <code>123456 Fulano da Silva</code><br>
                                <code>987654 - Ciclano de Souza</code>
                            </div>

                            <textarea name="lista_alunos" rows="15" required placeholder="Cole aqui a lista da chamada..."
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <a href="{{ route('ant.admin.home') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-300">Voltar</a>
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 font-bold shadow">
                                Processar Importação
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
