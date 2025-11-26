<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.home') }}" class="text-indigo-600 hover:underline">Dashboard</a> &rsaquo;
            {{ $trabalho->nome }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 flex justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Detalhes da Atividade</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $trabalho->materia->nome }}</p>
                    </div>
                    <div class="text-right">
                        @if($isAtrasado)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Encerrado / Atrasado</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aberto</span>
                        @endif
                        <p class="text-sm text-gray-500 mt-1">Prazo: {{ \Carbon\Carbon::parse($trabalho->prazo)->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $trabalho->descricao }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Formato Aceito</dt>
                            <dd class="mt-1 text-sm text-gray-900 uppercase">{{ $trabalho->tipoTrabalho->arquivos }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Máximo de Alunos</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $trabalho->maximo_alunos }} integrante(s)</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Sua Entrega</h3>
                </div>

                <div class="p-6">
                    @if($entrega)
                        <div class="mb-6 bg-blue-50 border border-blue-200 rounded p-4">
                            <h4 class="font-bold text-blue-800 mb-2">Último envio realizado em: {{ $entrega->data_entrega->format('d/m/Y H:i') }}</h4>

                            @if($entrega->nota !== null)
                                <div class="mt-2 text-lg font-bold text-gray-900">Nota: {{ $entrega->nota }}</div>
                                @if($entrega->comentario_professor)
                                    <p class="text-sm text-gray-600 mt-1"><strong>Feedback:</strong> {{ $entrega->comentario_professor }}</p>
                                @endif

                                <div class="mt-4 p-2 bg-green-100 text-green-800 text-sm font-bold rounded text-center border border-green-200">
                                    Trabalho avaliado. O reenvio está bloqueado.
                                </div>
                            @else
                                <p class="text-sm text-blue-600">Aguardando correção.</p>
                            @endif

                            <div class="mt-3">
                                <p class="text-sm font-medium text-gray-700">Arquivos enviados:</p>
                                <ul class="list-disc pl-5 text-sm text-gray-600">
                                    @foreach(json_decode($entrega->arquivos) as $arq)
                                        <li>
                                            @if(filter_var($arq, FILTER_VALIDATE_URL))
                                                <a href="{{ $arq }}" target="_blank" class="text-blue-600 underline">{{ $arq }}</a>
                                            @else
                                                {{ basename($arq) }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if(!$entrega || is_null($entrega->nota))

                        @if($entrega)
                            <hr class="my-6">
                            <p class="text-sm text-gray-500 mb-4">Deseja reenviar? O envio anterior será substituído.</p>
                        @endif

                        <form action="{{ route('ant.trabalhos.store', $trabalho->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            @if(str_contains($trabalho->tipoTrabalho->arquivos, 'link'))
                                <div class="mb-4">
                                    <label for="link" class="block text-sm font-medium text-gray-700">Link do Trabalho</label>
                                    <input type="url" name="link" id="link" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            @else
                                <div class="mb-4">
                                    <label for="arquivos" class="block text-sm font-medium text-gray-700">Selecione o(s) Arquivo(s)</label>
                                    <input id="arquivos" name="arquivos[]" type="file" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                </div>
                            @endif

                            <div class="mb-4">
                                <label for="comentario_aluno" class="block text-sm font-medium text-gray-700">Comentários (Opcional)</label>
                                <textarea id="comentario_aluno" name="comentario_aluno" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            </div>

                            <div class="text-right">
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Enviar Trabalho
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
