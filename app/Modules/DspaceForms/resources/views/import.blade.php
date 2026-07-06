<x-DspaceForms::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Importar XML') }}
            </h2>
            <a href="{{ route('dspace-forms.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold">
                &larr; Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Importar arquivo input-forms.xml (DSpace 6)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Os dados importados serão vinculados à configuração ativa:
                            <strong>{{ $config->name }}</strong>.
                            Dados existentes nesta configuração serão substituídos.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('dspace-forms.import.process') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <label for="xml_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Arquivo XML <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="xml_file" id="xml_file" accept=".xml"
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-indigo-50 file:text-indigo-700
                                          dark:file:bg-indigo-900 dark:file:text-indigo-200
                                          hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800
                                          cursor-pointer">
                            @error('xml_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="vocabulary_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Vocabulários (opcional)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                Envie um ZIP com os arquivos XML de vocabulário (cursos.xml, instituicoes.xml, eixo.xml, etc.)
                            </p>
                            <input type="file" name="vocabulary_file" id="vocabulary_file" accept=".zip"
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-indigo-50 file:text-indigo-700
                                          dark:file:bg-indigo-900 dark:file:text-indigo-200
                                          hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800
                                          cursor-pointer">
                            @error('vocabulary_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                                    onclick="this.disabled=true; this.form.submit(); this.innerText='Importando...';">
                                <i class="fa-solid fa-upload mr-2"></i>
                                Importar
                            </button>
                            <a href="{{ route('dspace-forms.index') }}"
                               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                Cancelar
                            </a>
                        </div>
                    </form>

                </div>
            </div>

            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Formato esperado</h4>
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    O arquivo deve ser um <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">input-forms.xml</code>
                    do DSpace 6 contendo as tags <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">&lt;form-map&gt;</code>,
                    <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">&lt;form-definitions&gt;</code> e
                    <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">&lt;form-value-pairs&gt;</code>.
                    O importador converte páginas e campos para o formato DSpace 8 automaticamente.
                </p>
            </div>
        </div>
    </div>
</x-DspaceForms::layout>
