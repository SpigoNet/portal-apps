<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Passo 1: Upload do Arquivo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 text-center">

                    <div class="mb-8">
                        <h3 class="text-lg font-medium">Envie sua lista de contatos</h3>
                        <p class="text-sm text-gray-500">O arquivo deve ser .CSV separado por vírgulas.</p>
                    </div>

                    <form action="{{ route('envio-whatsapp.upload') }}" method="POST" enctype="multipart/form-data" class="max-w-md mx-auto">
                        @csrf
                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="csv">Selecionar arquivo</label>
                            <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="csv" name="csv" type="file" required accept=".csv">
                            @error('csv')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                            Próximo: Configurar Mensagem
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
