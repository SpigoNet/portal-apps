<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Vincular Conta de Aluno') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <h3 class="text-lg font-medium text-gray-900">Bem-vindo ao Módulo ANT</h3>
                    <p class="mt-1 text-sm text-gray-600 mb-6">
                        Para acessar seus trabalhos e notas, informe seu RA abaixo para vincularmos ao seu usuário.
                    </p>

                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('ant.vincular_ra.store') }}" method="POST" class="max-w-md">
                        @csrf
                        <div class="mb-4">
                            <label for="ra" class="block text-sm font-medium text-gray-700">Registro Acadêmico (RA)</label>
                            <input type="text" name="ra" id="ra" required
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                   placeholder="Ex: 0000000000000">
                        </div>

                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Vincular RA
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
