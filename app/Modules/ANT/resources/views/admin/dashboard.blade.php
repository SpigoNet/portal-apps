<x-ANT::layout>
    <x-slot name="header">
        {{ __('Painel Administrativo - ANT') }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    Você está logado como Administrador do Módulo.
                    <br><br>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                        <!-- Card: Novo Trabalho -->
                        <a href="{{ route('ant.professor.create') }}"
                            class="group relative flex flex-col items-center justify-center p-8 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200">
                            <div class="p-4 bg-indigo-50 rounded-full group-hover:bg-indigo-100 transition-colors mb-4">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-indigo-700 transition-colors">
                                Novo Trabalho</h3>
                            <p class="text-sm text-gray-500 text-center mt-2">Adicionar avaliações ou trabalhos para as
                                turmas</p>
                        </a>

                        <!-- Card: Gerenciar Matérias -->
                        <a href="{{ route('ant.admin.materias.index') }}"
                            class="group relative flex flex-col items-center justify-center p-8 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200">
                            <div class="p-4 bg-blue-50 rounded-full group-hover:bg-blue-100 transition-colors mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">
                                Gerenciar Matérias</h3>
                            <p class="text-sm text-gray-500 text-center mt-2">Criar e editar disciplinas da grade
                                curricular</p>
                        </a>

                        <!-- Card: Gerenciar Professores -->
                        <a href="{{ route('ant.admin.professores.index') }}"
                            class="group relative flex flex-col items-center justify-center p-8 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200">
                            <div class="p-4 bg-purple-50 rounded-full group-hover:bg-purple-100 transition-colors mb-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path d="M12 14l9-5-9-5-9 5 9 5z" />
                                    <path
                                        d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-purple-700 transition-colors">
                                Gerenciar Professores</h3>
                            <p class="text-sm text-gray-500 text-center mt-2">Cadastro de docentes</p>
                        </a>

                        <!-- Card: Vincular Professores -->
                        <a href="{{ route('ant.admin.professores.create') }}"
                            class="group relative flex flex-col items-center justify-center p-8 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200">
                            <div class="p-4 bg-teal-50 rounded-full group-hover:bg-teal-100 transition-colors mb-4">
                                <svg class="w-8 h-8 text-teal-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-teal-700 transition-colors">
                                Vincular Professores</h3>
                            <p class="text-sm text-gray-500 text-center mt-2">Associar docentes a turmas e semestres</p>
                        </a>


                        <!-- Card: Gerenciar Alunos -->
                        <a href="{{ route('ant.admin.alunos.index') }}"
                            class="group relative flex flex-col items-center justify-center p-8 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-200">
                            <div class="p-4 bg-green-50 rounded-full group-hover:bg-green-100 transition-colors mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-green-700 transition-colors">
                                Gerenciar Alunos</h3>
                            <p class="text-sm text-gray-500 text-center mt-2">Listar e importar matrículas de alunos</p>
                        </a>


                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ANT::layout>