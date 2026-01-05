<x-TreeTask::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tarefa: {{ $tarefa->titulo }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 flex justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Detalhes da Tarefa</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Status atual: {{ $tarefa->fase->nome }}</p>
                    </div>
                    <a href="{{ route('treetask.show', $tarefa->fase->id_projeto) }}"
                       class="text-blue-600 hover:text-blue-900">Voltar ao Board</a>
                </div>
                <div class="border-t border-gray-200">
                    <dl>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                {!! nl2br($tarefa->descricao) !!}
                            </dd>
                        </div>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Responsável</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $tarefa->responsavel->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Prioridade</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $tarefa->prioridade }}</dd>
                        </div>
                    </dl>


                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Anexos</h3>

                    @if($tarefa->anexos->count() > 0)
                        <ul role="list" class="border border-gray-200 rounded-md divide-y divide-gray-200 mb-6">
                            @foreach($tarefa->anexos as $anexo)
                                <li class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                                    <div class="w-0 flex-1 flex items-center">
                                        <svg class="flex-shrink-0 h-5 w-5 text-gray-400"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                  d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                        <span class="ml-2 flex-1 w-0 truncate">
                            {{ $anexo->nome_arquivo }}
                            <span
                                class="text-gray-500 text-xs">({{ number_format($anexo->tamanho / 1024, 2) }} KB)</span>
                        </span>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 flex space-x-4">
                                        <a href="{{ route('treetask.anexos.download', $anexo->id_anexo) }}"
                                           class="font-medium text-blue-600 hover:text-blue-500">
                                            Baixar
                                        </a>

                                        <form
                                            action="{{ route('treetask.anexos.destroy', ['taskId' => $tarefa->id_tarefa, 'anexoId' => $anexo->id_anexo]) }}"
                                            method="POST" onsubmit="return confirm('Tem certeza?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                                                Remover
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 mb-6">Nenhum anexo encontrado.</p>
                    @endif

                    <div class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300">
                        <h4 class="text-sm font-bold text-gray-700 mb-2">Adicionar Novo Arquivo</h4>

                        <form action="{{ route('treetask.anexos.store', $tarefa->id_tarefa) }}" method="POST"
                              enctype="multipart/form-data" class="flex items-center space-x-4">
                            @csrf
                            <input type="file" name="arquivo" required class="block w-full text-sm text-slate-500
              file:mr-4 file:py-2 file:px-4
              file:rounded-full file:border-0
              file:text-sm file:font-semibold
              file:bg-blue-50 file:text-blue-700
              hover:file:bg-blue-100
            "/>

                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                                Enviar
                            </button>
                        </form>
                        @error('arquivo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <br><br>
                </div>

            </div>
        </div>
    </div>

</x-TreeTask::layout>
