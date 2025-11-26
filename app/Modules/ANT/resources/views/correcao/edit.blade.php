<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Corrigindo: {{ $entrega->aluno->nome }}
            </h2>
            <a href="{{ route('ant.home') }}" class="text-sm text-gray-500 hover:text-gray-900">Voltar</a>
        </div>
    </x-slot>

    <div class="flex h-screen" style="height: calc(100vh - 65px);">
        <div class="flex-1 bg-gray-200 overflow-hidden flex flex-col relative">

            <div class="bg-gray-100 border-b border-gray-300 flex overflow-x-auto">
                @foreach($arquivos as $idx => $arq)
                    <a href="{{ route('ant.correcao.edit', ['idEntrega' => $entrega->id, 'fileIndex' => $idx]) }}"
                       class="px-4 py-2 text-sm font-medium border-r border-gray-300 hover:bg-white whitespace-nowrap {{ $idx == $fileIndex ? 'bg-white text-indigo-600 border-t-2 border-t-indigo-600' : 'text-gray-500' }}">
                        Arquivo {{ $idx + 1 }} (.{{ pathinfo($arq, PATHINFO_EXTENSION) }})
                    </a>
                @endforeach
            </div>

            <div class="flex-1 overflow-auto p-4 flex justify-center items-center">
                @include('ANT::correcao.renderers.' . $dadosVisualizacao['tipo'], ['data' => $dadosVisualizacao])
            </div>
        </div>

        <div class="w-96 bg-white border-l border-gray-200 shadow-xl overflow-y-auto p-6 z-10">
            <h3 class="font-bold text-lg mb-1">{{ $entrega->trabalho->nome }}</h3>
            <p class="text-sm text-gray-500 mb-6">{{ $entrega->trabalho->materia->nome }}</p>

            <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                <h4 class="text-xs font-bold text-blue-800 uppercase mb-1">Comentário do Aluno</h4>
                <p class="text-sm text-gray-700 italic">"{{ $entrega->comentario_aluno ?? 'Sem comentários.' }}"</p>
            </div>

            <hr class="my-6">
            <div class="mb-4">
                <button type="button" id="btn-ia" onclick="solicitarIa()"
                        class="w-full bg-purple-600 text-white font-bold py-2 px-4 rounded hover:bg-purple-700 transition flex items-center justify-center gap-2">
                    <span class="material-icons">auto_awesome</span>
                    Sugerir Nota e Feedback (IA)
                </button>
                <p id="ia-loading" class="text-xs text-center text-gray-500 mt-2 hidden">
                    <span class="animate-spin inline-block mr-1">↻</span> Analisando código do aluno...
                </p>
                <p id="ia-error" class="text-xs text-center text-red-500 mt-2 hidden"></p>
            </div>
            <form action="{{ route('ant.correcao.update', $entrega->id) }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nota (0 a 10)</label>
                    <input type="number" step="0.1" min="0" max="10" name="nota" id="input-nota"
                           value="{{ $entrega->nota }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-bold">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Feedback do Professor</label>
                    <textarea name="comentario_professor" id="input-comentario" rows="6"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $entrega->comentario_professor }}</textarea>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded hover:bg-indigo-700 transition">
                    Salvar Correção
                </button>
            </form>

            @if(session('success'))
                <div class="mt-4 p-3 bg-green-100 text-green-700 rounded text-sm text-center">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    </div>
    <script>
        function solicitarIa() {
            const btn = document.getElementById('btn-ia');
            const loading = document.getElementById('ia-loading');
            const errorMsg = document.getElementById('ia-error');
            const inputNota = document.getElementById('input-nota');
            const inputComentario = document.getElementById('input-comentario');

            // Estado de Carregamento
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            loading.classList.remove('hidden');
            errorMsg.classList.add('hidden');

            fetch("{{ route('ant.correcao.ia_sugestao', $entrega->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // Preenche os campos com efeito visual
                    inputNota.value = data.nota;
                    inputComentario.value = data.feedback;

                    // Efeito de "Flash" para mostrar que atualizou
                    inputNota.classList.add('bg-green-100');
                    inputComentario.classList.add('bg-green-100');
                    setTimeout(() => {
                        inputNota.classList.remove('bg-green-100');
                        inputComentario.classList.remove('bg-green-100');
                    }, 1000);

                })
                .catch(error => {
                    console.error(error);
                    errorMsg.innerText = "Erro: " + error.message;
                    errorMsg.classList.remove('hidden');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    loading.classList.add('hidden');
                });
        }
    </script>
</x-app-layout>
