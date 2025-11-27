<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ferramenta de Envio WhatsApp') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- Exibe Erros de Validação do Laravel --}}
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Lógica: Se não houver resultados, exibe o FORMULÁRIO --}}
                    @if (!isset($resultados))

                        <form action="{{ route('envio-whatsapp.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Coluna da Esquerda: Arquivo e Configuração --}}
                                <div>
                                    <h4 class="text-lg font-bold mb-4">1. Arquivo CSV</h4>

                                    <div class="mb-4">
                                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="csv">Upload CSV</label>
                                        <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="csv" name="csv" type="file" required accept=".csv,.txt">
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="csv_nome" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Coluna Nome (Nº)</label>
                                            <input type="number" name="csv_nome" id="csv_nome" value="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                        </div>
                                        <div>
                                            <label for="csv_telefone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Coluna Tel. (Nº)</label>
                                            <input type="number" name="csv_telefone" id="csv_telefone" value="2" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                        </div>
                                    </div>
                                </div>

                                {{-- Coluna da Direita: Mensagem --}}
                                <div>
                                    <h4 class="text-lg font-bold mb-2">2. Mensagem</h4>
                                    <p class="text-sm text-gray-500 mb-2">Use <strong>%nome%</strong> para substituir pelo nome da pessoa.</p>

                                    <textarea name="msg" rows="6" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Olá %nome%, tudo bem?..." required></textarea>
                                </div>
                            </div>

                            <div class="flex justify-center mt-6">
                                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                    Processar e Listar
                                </button>
                            </div>
                        </form>

                        {{-- Lógica: Se houver resultados, exibe a LISTA --}}
                    @else

                        <div class="text-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Lista de Envio</h3>
                            <a href="{{ route('envio-whatsapp.index') }}" class="text-white bg-gray-500 hover:bg-gray-600 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 dark:bg-gray-600 dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                                Voltar / Novo Envio
                            </a>
                        </div>

                        {{-- Lista de Erros de Processamento (se houver) --}}
                        @if (!empty($erros))
                            <div class="mb-6 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                                <p class="font-bold">Atenção aos itens ignorados:</p>
                                <ul class="list-disc pl-5">
                                    @foreach ($erros as $erro)
                                        <li>{{ $erro }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Lista de Links Gerados --}}
                        <div class="grid grid-cols-1 gap-4">
                            @foreach ($resultados as $item)
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-lg">{{ $item['nome'] }}</p>
                                        <p class="text-gray-600 dark:text-gray-400">{{ $item['telefone_display'] }}</p>
                                    </div>
                                    <a href="{{ $item['link'] }}" target="_blank" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-500 dark:hover:bg-green-600 focus:outline-none dark:focus:ring-green-800">
                                        Enviar WhatsApp
                                    </a>
                                </div>
                            @endforeach

                            @if(count($resultados) == 0 && empty($erros))
                                <p class="text-center text-gray-500">Nenhum contato válido encontrado no arquivo.</p>
                            @endif
                        </div>

                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
