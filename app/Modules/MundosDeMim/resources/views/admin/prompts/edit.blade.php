<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gerenciar Prompt & Requisitos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-700">Editar Prompt do Tema: {{ $prompt->theme->name }}</h3>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">ID: {{ $prompt->id }}</span>
                </div>

                <form action="{{ route('mundos-de-mim.admin.prompts.update', $prompt->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="theme_id" value="{{ $prompt->theme_id }}">

                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Texto do Prompt (Inglês)</label>
                        <textarea name="prompt_text" rows="5" required
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                        >{{ old('prompt_text', $prompt->prompt_text) }}</textarea>
                        <p class="text-xs text-gray-500 mt-2">
                            Variáveis disponíveis: <code>{name}, {height}, {eye_color}, {hair_type}, {pet_name}...</code>
                        </p>
                    </div>

                    <div class="border-t border-gray-200 my-6 pt-6">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-indigo-700">Requisitos de Ativação</h3>
                                <p class="text-sm text-gray-500">
                                    Este prompt só será usado se o usuário cumprir TODAS as condições abaixo.
                                </p>
                            </div>
                            <button type="button" onclick="addRequirementRow()"
                                    class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-1 rounded text-sm font-bold hover:bg-indigo-100 transition-colors">
                                + Adicionar Regra
                            </button>
                        </div>

                        <div id="requirements-container" class="space-y-3 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            @foreach($prompt->requirements as $index => $req)
                                <div class="flex items-center gap-2 bg-white p-2 rounded border shadow-sm requirement-row" id="row-{{ $index }}">
                                    <div class="w-1/3">
                                        <label class="text-xs text-gray-400 block mb-1">Campo</label>
                                        <select name="requirements[{{ $index }}][key]" class="w-full text-sm rounded-md border-gray-300">
                                            <option value="has_relationship" {{ $req->requirement_key == 'has_relationship' ? 'selected' : '' }}>Possui Relacionamento</option>
                                            <option value="body_type" {{ $req->requirement_key == 'body_type' ? 'selected' : '' }}>Tipo de Corpo</option>
                                            <option value="eye_color" {{ $req->requirement_key == 'eye_color' ? 'selected' : '' }}>Cor dos Olhos</option>
                                            <option value="hair_type" {{ $req->requirement_key == 'hair_type' ? 'selected' : '' }}>Tipo de Cabelo</option>
                                            <option value="min_height" {{ $req->requirement_key == 'min_height' ? 'selected' : '' }}>Altura Mínima (cm)</option>
                                            <option value="max_weight" {{ $req->requirement_key == 'max_weight' ? 'selected' : '' }}>Peso Máximo (kg)</option>
                                        </select>
                                    </div>
                                    <div class="w-1/6">
                                        <label class="text-xs text-gray-400 block mb-1">Operador</label>
                                        <select name="requirements[{{ $index }}][operator]" class="w-full text-sm rounded-md border-gray-300 text-center font-mono">
                                            <option value="=" {{ $req->operator == '=' ? 'selected' : '' }}>= (Igual)</option>
                                            <option value="!=" {{ $req->operator == '!=' ? 'selected' : '' }}>!= (Diferente)</option>
                                            <option value=">" {{ $req->operator == '>' ? 'selected' : '' }}>&gt; (Maior)</option>
                                            <option value="<" {{ $req->operator == '<' ? 'selected' : '' }}>&lt; (Menor)</option>
                                        </select>
                                    </div>
                                    <div class="w-1/3">
                                        <label class="text-xs text-gray-400 block mb-1">Valor</label>
                                        <input type="text" name="requirements[{{ $index }}][value]" value="{{ $req->requirement_value }}"
                                               class="w-full text-sm rounded-md border-gray-300" placeholder="Ex: Pet, Blue, 170...">
                                    </div>
                                    <div class="pt-5">
                                        <button type="button" onclick="removeRow({{ $index }})" class="text-red-400 hover:text-red-600 p-2" title="Remover regra">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            @if($prompt->requirements->isEmpty())
                                <p id="no-req-msg" class="text-sm text-gray-400 text-center italic py-2">Nenhuma restrição configurada (Prompt Universal)</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-8">
                        <a href="{{ route('mundos-de-mim.admin.themes.edit', $prompt->theme_id) }}" class="px-6 py-3 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium shadow-sm">
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-md shadow-md hover:bg-indigo-700 font-bold transition-transform transform hover:scale-105">
                            Salvar Alterações
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        // Começa a contagem baseada no que já existe para não sobrepor índices
        let reqCount = {{ $prompt->requirements->count() + 1 }};

        function addRequirementRow() {
            // Remove mensagem de vazio se existir
            const msg = document.getElementById('no-req-msg');
            if(msg) msg.style.display = 'none';

            const container = document.getElementById('requirements-container');

            const html = `
                <div class="flex items-center gap-2 bg-white p-2 rounded border shadow-sm requirement-row animate-fade-in-down" id="row-${reqCount}">
                    <div class="w-1/3">
                        <label class="text-xs text-gray-400 block mb-1">Novo Campo</label>
                        <select name="requirements[${reqCount}][key]" class="w-full text-sm rounded-md border-gray-300 bg-yellow-50">
                            <option value="">-- Selecione --</option>
                            <option value="has_relationship">Possui Relacionamento</option>
                            <option value="body_type">Tipo de Corpo</option>
                            <option value="eye_color">Cor dos Olhos</option>
                            <option value="hair_type">Tipo de Cabelo</option>
                            <option value="min_height">Altura Mínima (cm)</option>
                        </select>
                    </div>
                    <div class="w-1/6">
                        <label class="text-xs text-gray-400 block mb-1">Op</label>
                        <select name="requirements[${reqCount}][operator]" class="w-full text-sm rounded-md border-gray-300 text-center font-mono">
                            <option value="=">=</option>
                            <option value="!=">!=</option>
                            <option value=">">&gt;</option>
                            <option value="<">&lt;</option>
                        </select>
                    </div>
                    <div class="w-1/3">
                        <label class="text-xs text-gray-400 block mb-1">Valor</label>
                        <input type="text" name="requirements[${reqCount}][value]" placeholder="Valor..." class="w-full text-sm rounded-md border-gray-300">
                    </div>
                    <div class="pt-5">
                        <button type="button" onclick="removeRow(${reqCount})" class="text-red-400 hover:text-red-600 p-2">🗑️</button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            reqCount++;
        }

        function removeRow(id) {
            const row = document.getElementById(`row-${id}`);
            if(row) row.remove();
        }
    </script>

    <style>
        .animate-fade-in-down {
            animation: fadeInDown 0.3s ease-out;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>
