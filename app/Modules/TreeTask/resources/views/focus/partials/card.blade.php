<div class="p-4 hover:bg-gray-50 transition duration-150 ease-in-out flex items-center justify-between group">

    <div class="flex-1 min-w-0 pr-4">
        <a href="{{ route('treetask.tarefas.edit', ['id' => $tarefa->id_tarefa]) }}" class="block focus:outline-none">

            <div class="flex items-center mb-1">
                <span class="text-lg font-medium text-gray-900 truncate group-hover:text-blue-600">
                    {{ $tarefa->titulo }}
                </span>

                @if($tarefa->prioridade == 'Urgente')
                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                        URGENTE
                    </span>
                @elseif($tarefa->prioridade == 'Alta')
                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                        Alta
                    </span>
                @endif
            </div>

            <div class="flex items-center text-sm text-gray-500">
                <span class="font-semibold text-gray-600">{{ $tarefa->fase->projeto->nome }}</span>
                <svg class="w-3 h-3 mx-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                <span>{{ $tarefa->fase->nome }}</span>
            </div>
        </a>
    </div>

    <div class="flex items-center space-x-4">

        @if($tarefa->data_vencimento)
            <div class="text-right">
                <p class="text-sm font-medium {{ $urgente ? 'text-red-600' : 'text-gray-900' }}">
                    {{ \Carbon\Carbon::parse($tarefa->data_vencimento)->format('d/m') }}
                </p>
                <p class="text-xs text-gray-500">Vencimento</p>
            </div>
        @else
            <div class="text-right text-xs text-gray-400">
                Sem data
            </div>
        @endif

        <a href="{{ route('treetask.tarefas.edit', ['id' => $tarefa->id_tarefa]) }}" class="text-gray-400 hover:text-blue-600 p-2 rounded-full hover:bg-blue-50">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
        </a>
    </div>
</div>
