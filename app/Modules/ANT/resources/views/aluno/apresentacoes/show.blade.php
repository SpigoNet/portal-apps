<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.apresentacoes.aluno.index') }}" class="text-gray-500 hover:text-gray-900">Minhas Apresentações</a>
            <span class="text-gray-400 mx-2">/</span>
            Detalhes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="text-sm text-gray-500">{{ $apresentador->agendamento->apresentacao->materia->nome }}</div>
                <div class="font-bold text-gray-900 text-lg">{{ $apresentador->agendamento->apresentacao->nome }}</div>
                <div class="text-gray-600 mt-1">
                    📅 {{ \Carbon\Carbon::parse($apresentador->agendamento->data)->format('d/m/Y') }}
                    · Tema: <strong>{{ $apresentador->agendamento->tema }}</strong>
                </div>
                @if($apresentador->agendamento->apresentacao->descricao)
                    <p class="text-sm text-gray-600 mt-3">{{ $apresentador->agendamento->apresentacao->descricao }}</p>
                @endif
                @if($apresentador->nota !== null)
                    <div class="mt-3 inline-block bg-green-50 text-green-800 px-3 py-1 rounded text-sm">
                        Sua nota na apresentação: <strong>{{ number_format($apresentador->nota, 1) }}</strong>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold text-gray-700 mb-3">Enviar material da apresentação</h3>

                @if($apresentador->entrega)
                    <div class="mb-4 text-sm text-gray-600">
                        <span class="text-green-600 font-semibold">✓ Entrega enviada em
                            {{ $apresentador->entrega->data_entrega->format('d/m/Y H:i') }}</span>
                        <ul class="list-disc list-inside mt-1">
                            @foreach(json_decode($apresentador->entrega->arquivos, true) ?? [] as $arquivo)
                                <li class="break-all">{{ $arquivo }}</li>
                            @endforeach
                        </ul>
                        <p class="text-xs text-gray-400 mt-1">Reenviar substituirá os arquivos atuais.</p>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('ant.apresentacoes.aluno.entregar', $apresentador->agendamento_id) }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Arquivos (apresentação, slides, etc.)</label>
                        <input type="file" name="arquivos[]" multiple
                               class="mt-1 block w-full text-sm">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Ou link (YouTube, Drive...)</label>
                        <input type="url" name="link"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                               placeholder="https://...">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Comentário (opcional)</label>
                        <textarea name="comentario_aluno" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ $apresentador->entrega?->comentario_aluno ?? '' }}</textarea>
                    </div>

                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold">
                        {{ $apresentador->entrega ? 'Atualizar entrega' : 'Enviar entrega' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-ANT::layout>
