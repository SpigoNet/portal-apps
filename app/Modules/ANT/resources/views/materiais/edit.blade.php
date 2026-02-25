<x-ANT::layout>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Material
        </h2>
        <p class="text-sm text-gray-500 mt-1">{{ $materia->nome }} — {{ $semestreAtual }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if($errors->any())
                    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="material-form" method="POST" action="{{ route('ant.materiais.update', $material->id) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="descricao" id="descricao-input">

                    <div class="space-y-5">

                        <div>
                            <label for="data_aula" class="block text-sm font-medium text-gray-700">
                                Data da Aula
                            </label>
                            <input type="date" id="data_aula" name="data_aula"
                                value="{{ old('data_aula', $material->data_aula->format('Y-m-d')) }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="titulo" class="block text-sm font-medium text-gray-700">
                                Título
                            </label>
                            <input type="text" id="titulo" name="titulo"
                                value="{{ old('titulo', $material->titulo) }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Conteúdo
                                <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                            </label>
                            <div id="editor-container" style="min-height: 200px;">
                                {!! old('descricao', $material->descricao) !!}
                            </div>
                        </div>

                        @if($arquivosExistentes)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Anexos Existentes
                                </label>
                                <div class="space-y-2">
                                    @foreach($arquivosExistentes as $caminho)
                                        <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                                            <span class="text-sm text-gray-700 truncate">{{ basename($caminho) }}</span>
                                            <label class="flex items-center gap-1 ml-3 text-xs text-red-600 cursor-pointer flex-shrink-0">
                                                <input type="checkbox" name="remover_arquivos[]" value="{{ $caminho }}"
                                                    class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                                Remover
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="novos_arquivos" class="block text-sm font-medium text-gray-700">
                                Adicionar Novos Arquivos
                                <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                            </label>
                            <input type="file" id="novos_arquivos" name="novos_arquivos[]"
                                multiple
                                class="mt-1 block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-gray-400">
                                PDF, PPTX, DOCX, ZIP, imagens e outros formatos. Máximo de 50 MB por arquivo.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Vídeos do YouTube
                                <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                            </label>
                            <div id="videos-container" class="space-y-2">
                                @forelse($videosExistentes as $videoUrl)
                                    <div class="flex gap-2 items-center video-row">
                                        <input type="url" name="videos[]"
                                            value="{{ $videoUrl }}"
                                            placeholder="https://www.youtube.com/watch?v=..."
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        <button type="button" onclick="removerVideo(this)"
                                            class="text-red-400 hover:text-red-600 transition p-1" title="Remover">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @empty
                                    <div class="flex gap-2 items-center video-row">
                                        <input type="url" name="videos[]"
                                            placeholder="https://www.youtube.com/watch?v=..."
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        <button type="button" onclick="removerVideo(this)"
                                            class="text-red-400 hover:text-red-600 transition p-1" title="Remover">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                            <button type="button" onclick="adicionarVideo()"
                                class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                + Adicionar vídeo
                            </button>
                        </div>

                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('ant.materiais.index', $materia->id) }}"
                            class="text-sm text-gray-600 hover:text-gray-900">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 shadow-sm text-sm font-medium transition">
                            Salvar Alterações
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                placeholder: 'Descreva o conteúdo da aula, adicione links, formatação...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', 'code-block'],
                        [{ 'header': [1, 2, 3, false] }],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['link'],
                        ['clean']
                    ]
                }
            });

            var form = document.getElementById('material-form');
            form.addEventListener('submit', function () {
                var input = document.getElementById('descricao-input');
                if (quill.root.innerHTML === '<p><br></p>') {
                    input.value = '';
                } else {
                    input.value = quill.root.innerHTML;
                }
            });
        });

        function adicionarVideo() {
            var container = document.getElementById('videos-container');
            var row = document.createElement('div');
            row.className = 'flex gap-2 items-center video-row';
            row.innerHTML = '<input type="url" name="videos[]" placeholder="https://www.youtube.com/watch?v=..." class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">' +
                '<button type="button" onclick="removerVideo(this)" class="text-red-400 hover:text-red-600 transition p-1" title="Remover">' +
                '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>' +
                '</button>';
            container.appendChild(row);
        }

        function removerVideo(btn) {
            var container = document.getElementById('videos-container');
            var rows = container.querySelectorAll('.video-row');
            if (rows.length > 1) {
                btn.closest('.video-row').remove();
            } else {
                btn.closest('.video-row').querySelector('input').value = '';
            }
        }
    </script>
</x-ANT::layout>
