<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.professor.index') }}" class="text-gray-500 hover:text-gray-900">Dashboard</a>
            <span class="text-gray-400 mx-2">/</span>
            Novo Trabalho
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <form action="{{ route('ant.professor.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Disciplina</label>
                                <select name="materia_id" id="materia_id" onchange="filtrarPesos()" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selecione...</option>
                                    @foreach($materias as $materia)
                                        <option value="{{ $materia->id }}">{{ $materia->nome }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo de Entrega</label>
                                <select name="tipo_trabalho_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($tipos as $tipo)
                                        <option value="{{ $tipo->id }}">
                                            {{ $tipo->descricao }} ({{ $tipo->arquivos }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Atribuição de Nota (Peso)</label>
                                <select name="peso_id" id="peso_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-50">
                                    <option value="">Selecione a Matéria primeiro...</option>
                                    @foreach($pesos as $peso)
                                        <option value="{{ $peso->id }}" data-materia="{{ $peso->materia_id }}" style="display:none;">
                                            {{ $peso->grupo }} (Valor: {{ $peso->valor }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Selecione qual nota este trabalho irá compor.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Prazo de Entrega</label>
                                <input type="date" name="prazo" required min="{{ date('Y-m-d') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Título do Trabalho</label>
                            <input type="text" name="nome" required placeholder="Ex: Lista de Exercícios 1 - SQL"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Descrição / Enunciado</label>
                            <textarea name="descricao" rows="5" required placeholder="Descreva o que o aluno deve fazer..."
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Máximo de Alunos (Grupo)</label>
                                <input type="number" name="maximo_alunos" value="1" min="1" max="10"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <label class="block text-sm font-bold text-purple-800 flex items-center">
                                    <span class="material-icons text-sm mr-1">auto_awesome</span>
                                    Dicas para a IA Corretora (Opcional)
                                </label>
                                <textarea name="dicas_correcao" rows="2" placeholder="Ex: O aluno deve usar INNER JOIN. Descontar nota se não formatar o código."
                                          class="mt-1 block w-full rounded-md border-purple-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"></textarea>
                                <p class="text-xs text-purple-600 mt-1">Essas instruções ajudarão o agente de IA a sugerir notas e feedbacks melhores.</p>
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-gray-100 pt-4">
                            <a href="{{ route('ant.professor.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-300">Cancelar</a>
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 shadow-md">
                                Criar Trabalho
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filtrarPesos() {
            const materiaId = document.getElementById('materia_id').value;
            const selectPeso = document.getElementById('peso_id');
            const options = selectPeso.querySelectorAll('option');

            let primeiroVisivel = null;

            selectPeso.value = ""; // Reseta seleção
            selectPeso.classList.remove('bg-gray-50');

            options.forEach(opt => {
                if (opt.value === "") return; // Pula o placeholder

                if (opt.dataset.materia == materiaId) {
                    opt.style.display = 'block';
                    if (!primeiroVisivel) primeiroVisivel = opt.value;
                } else {
                    opt.style.display = 'none';
                }
            });

            if(materiaId === "") {
                selectPeso.classList.add('bg-gray-50');
            }
        }
    </script>
</x-app-layout>
