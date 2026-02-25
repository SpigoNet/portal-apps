<x-ANT::layout>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Publicar Material
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

                <form id="material-form" method="POST" action="{{ route('ant.materiais.store', $materia->id) }}"
                    enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="descricao" id="descricao-input">

                    <div class="space-y-5">

                        <div>
                            <label for="data_aula" class="block text-sm font-medium text-gray-700">
                                Data da Aula
                            </label>
                            <input type="date" id="data_aula" name="data_aula"
                                value="{{ old('data_aula', date('Y-m-d')) }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="titulo" class="block text-sm font-medium text-gray-700">
                                Título
                            </label>
                            <input type="text" id="titulo" name="titulo"
                                value="{{ old('titulo') }}"
                                placeholder="Ex: Slides Aula 3 — Arrays e Listas"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Conteúdo
                                <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                            </label>
                            <div id="editor-container" style="min-height: 200px;">
                                {!! old('descricao') !!}
                            </div>
                        </div>

                        <div>
                            <label for="arquivos" class="block text-sm font-medium text-gray-700">
                                Arquivos Anexos
                                <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                            </label>
                            <input type="file" id="arquivos" name="arquivos[]"
                                multiple
                                class="mt-1 block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-gray-400">
                                PDF, PPTX, DOCX, ZIP, imagens e outros formatos. Máximo de 50 MB por arquivo.
                                Você pode selecionar múltiplos arquivos.
                            </p>
                        </div>

                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('ant.materiais.index', $materia->id) }}"
                            class="text-sm text-gray-600 hover:text-gray-900">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 shadow-sm text-sm font-medium transition">
                            Publicar Material
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
    </script>
</x-ANT::layout>
