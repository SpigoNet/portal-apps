<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel Administrativo - ANT') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    Você está logado como Administrador do Módulo.
                    <br><br>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <a href="{{ route('ant.admin.materias.index') }}" class="p-6 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition text-center">
                            <span class="material-icons text-4xl text-indigo-600 mb-2">library_books</span>
                            <h3 class="text-lg font-bold text-indigo-900">Gerenciar Matérias</h3>
                            <p class="text-sm text-indigo-600">Criar e editar disciplinas</p>
                        </a>

                        <a href="{{ route('ant.admin.professores.index') }}" class="p-6 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition text-center">
                            <span class="material-icons text-4xl text-indigo-600 mb-2">school</span>
                            <h3 class="text-lg font-bold text-indigo-900">Gerenciar Professores</h3>
                            <p class="text-sm text-indigo-600">Atribuir aulas e turmas</p>
                        </a>

                        <a href="{{ route('ant.admin.alunos.index') }}" class="p-6 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition text-center">
                            <span class="material-icons text-4xl text-indigo-600 mb-2">groups</span>
                            <h3 class="text-lg font-bold text-indigo-900">Gerenciar Alunos</h3>
                            <p class="text-sm text-indigo-600">Listar e Importar Matrículas</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
