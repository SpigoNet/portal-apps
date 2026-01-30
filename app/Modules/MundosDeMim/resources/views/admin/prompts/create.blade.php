<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Novo Prompt para: {{ $theme->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('mundos-de-mim.admin.prompts.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="theme_id" value="{{ $theme->id }}">

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Texto do Prompt (Inglês)</label>
                        <p class="text-xs text-gray-500 mb-2">Use variáveis como {hair_type}, {eye_color}, {pet_name}...</p>
                        <textarea name="prompt_text" rows="4" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <hr class="my-6">

                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Requisitos de Elegibilidade</h3>
                            <button type="button" onclick="addRequirementRow()" class="text-sm text-indigo-600 font-bold hover:underline">
                                + Adicionar Condição
                            </button>
                        </div>

                        <p class="text-sm text-gray-500 mb-4">
                            Defina quem pode receber este prompt. Se deixar vazio, todos receberão.
                        </p>

                        <div id="requirements-container" class="space-y-3">
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('mundos-de-mim.admin.themes.edit', $theme->id) }}" class="px-4 py-2 bg-gray-200 rounded text-gray-700">Cancelar</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700">Salvar Prompt</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        let reqCount = 0;

        function addRequirementRow() {
            const container = document.getElementById('requirements-container');

            const html = `
                <div class="flex items-center gap-2 bg-gray-50 p-2 rounded border" id="row-${reqCount}">
                    <div class="w-1/3">
                        <select name="requirements[${reqCount}][key]" class="w-full text-sm rounded-md border-gray-300">
                            <option value="">-- Selecione a Regra --</option>
                            <option value="has_relationship">Tem Relacionamento (Pessoa/Pet)</option>
                            <option value="body_type">Tipo de Corpo</option>
                            <option value="hair_type">Tipo de Cabelo</option>
                            <option value="min_height">Altura Mínima (cm)</option>
                            <option value="eye_color">Cor dos Olhos</option>
                        </select>
                    </div>
                    <div class="w-1/6">
                        <select name="requirements[${reqCount}][operator]" class="w-full text-sm rounded-md border-gray-300">
                            <option value="=">Igual a (=)</option>
                            <option value="!=">Diferente de (!=)</option>
                            <option value=">">Maior que (>)</option>
                        </select>
                    </div>
                    <div class="w-1/3">
                        <input type="text" name="requirements[${reqCount}][value]" placeholder="Ex: Pet, Blue, 160..." class="w-full text-sm rounded-md border-gray-300">
                    </div>
                    <button type="button" onclick="removeRow(${reqCount})" class="text-red-500 hover:text-red-700 font-bold px-2">X</button>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            reqCount++;
        }

        function removeRow(id) {
            document.getElementById(`row-${id}`).remove();
        }
    </script>
</x-app-layout>
