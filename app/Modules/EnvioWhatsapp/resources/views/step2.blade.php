<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Passo 2: Configuração') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <form action="{{ route('envio-whatsapp.process') }}" method="POST">
                        @csrf
                        <input type="hidden" name="file_path" value="{{ $path }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                            {{-- Coluna da Esquerda: Definição da Coluna de Telefone --}}
                            <div>
                                <h3 class="text-lg font-bold mb-4 border-b pb-2">Identifique a coluna do Telefone</h3>
                                <p class="text-sm text-gray-500 mb-4">Selecione abaixo qual coluna do seu arquivo contém o número do celular.</p>

                                <label for="coluna_telefone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Coluna do Telefone</label>
                                <select id="coluna_telefone" name="coluna_telefone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                                    @foreach($headers as $index => $header)
                                        <option value="{{ $index }}">
                                            {{ $header }} (Ex: {{ $preview[$index] ?? '...' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Coluna da Direita: Mensagem e Variáveis --}}
                            <div>
                                <h3 class="text-lg font-bold mb-4 border-b pb-2">Escreva sua Mensagem</h3>

                                <div class="mb-4">
                                    <p class="text-sm font-semibold mb-2">Variáveis disponíveis:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($headers as $header)
                                            <span class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-semibold px-2.5 py-0.5 rounded cursor-pointer hover:bg-gray-300" onclick="insertVar('%{{ trim($header) }}%')">
                                                %{{ trim($header) }}%
                                            </span>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Clique nas tags acima para inserir no texto.</p>
                                </div>

                                <textarea id="msg_area" name="msg" rows="8" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Olá %Nome%, sua fatura vence dia %Vencimento%..." required></textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-8 border-t pt-4">
                            <a href="{{ route('envio-whatsapp.index') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm">
                                &larr; Voltar e enviar outro arquivo
                            </a>
                            <button type="submit" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-bold rounded-lg text-base px-8 py-3 dark:bg-green-500 dark:hover:bg-green-600 focus:outline-none dark:focus:ring-green-800">
                                Gerar Lista de Envio &rarr;
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Script simples para inserir variável no textarea --}}
    <script>
        function insertVar(text) {
            const textarea = document.getElementById('msg_area');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const value = textarea.value;
            textarea.value = value.substring(0, start) + text + value.substring(end);
            textarea.focus();
            textarea.selectionEnd = start + text.length;
        }
    </script>
</x-app-layout>
