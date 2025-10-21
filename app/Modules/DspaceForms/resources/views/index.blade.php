<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editor de Configurações DSpace') }}
            </h2>
            <a href="{{ route('dspace-forms.export.zip') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                <i class="fa-solid fa-file-zipper mr-2"></i>
                Exportar Configurações (.zip)
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Card de Formulários -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex items-center">
                            <i class="fa-solid fa-wpforms fa-2x text-indigo-500 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">Formulários de Submissão</h3>
                                <p class="text-2xl font-bold">{{ $stats['forms_count'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">formulários configurados</p>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="#" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                Gerenciar Formulários &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card de Vocabulários -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex items-center">
                            <i class="fa-solid fa-list-check fa-2x text-green-500 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">Vocabulários Controlados</h3>
                                <p class="text-2xl font-bold">{{ $stats['vocabularies_count'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">listas de valores gerenciadas</p>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="#" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                                Gerenciar Vocabulários &rarr;
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>

