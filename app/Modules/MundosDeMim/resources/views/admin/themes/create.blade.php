<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Novo Tema
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('mundos-de-mim.admin.themes.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <h3 class="text-lg font-bold mb-4 border-b pb-2">1. Dados Básicos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome do Tema</label>
                            <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug (URL)</label>
                            <input type="text" name="slug" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Classificação Etária</label>
                            <select name="age_rating" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="kids">Kids (Livre)</option>
                                <option value="teen">Teen (12+)</option>
                                <option value="adult">Adult (18+)</option>
                            </select>
                        </div>
                        <div class="flex items-center pt-6">
                            <input type="checkbox" name="is_seasonal" id="is_seasonal" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                            <label for="is_seasonal" class="ml-2 text-sm text-gray-900">Tema Sazonal (Evento)?</label>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold mb-4 border-b pb-2 mt-8">2. Imagens de Exibição (Catálogo)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                        <div class="bg-gray-50 p-4 rounded border border-dashed border-gray-300">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Foto de Exemplo "ANTES"</label>
                            <p class="text-xs text-gray-500 mb-2">A foto que o usuário deve enviar (ex: Casal, Rosto, Pet).</p>

                            <input type="file" name="example_input" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mb-3">

                            <label class="block text-xs font-bold text-gray-600">Descrição Curta</label>
                            <input type="text" name="example_input_description" placeholder="Ex: Foto de Casal" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="bg-indigo-50 p-4 rounded border border-dashed border-indigo-300">
                            <label class="block text-sm font-bold text-indigo-900 mb-2">Exemplos de Resultado "DEPOIS"</label>
                            <p class="text-xs text-indigo-700 mb-2">Selecione uma ou mais imagens geradas por IA para este estilo.</p>

                            <input type="file" name="example_outputs[]" multiple accept="image/*" class="block w-full text-sm text-indigo-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-white file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded shadow hover:bg-indigo-700 font-bold">
                            Criar Tema
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
