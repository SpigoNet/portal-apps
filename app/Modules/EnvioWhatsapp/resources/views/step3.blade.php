<x-app-layout
    :module-id="5"
    :module-menu="view('EnvioWhatsapp::components.menu-main')"
>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Passo 3: Enviar Mensagens') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold">Lista Pronta</h3>
                        <button onclick="resetHistory()" class="text-xs text-red-500 underline hover:text-red-700">
                            Limpar histórico de cliques deste arquivo
                        </button>
                    </div>

                    @if(!empty($erros))
                        <div class="mb-6 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                            <p class="font-bold">Registros ignorados (formato inválido): {{ count($erros) }}</p>
                        </div>
                    @endif

                    <div class="space-y-3">
                        @foreach ($resultados as $item)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg transition-colors duration-200"
                                 id="card-{{ $item['id'] }}">

                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-lg text-gray-800 dark:text-white">
                                            {{ $item['telefone'] }}
                                        </span>
                                        {{-- Exibe primeira chave extra como nome se existir --}}
                                        @php $keys = array_keys($item['dados']); @endphp
                                        @if(isset($keys[0]))
                                            <span class="text-sm text-gray-500">({{ $item['dados'][$keys[0]] }})</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1 truncate max-w-xl">
                                        Link gerado...
                                    </div>
                                </div>

                                <div>
                                    <a href="{{ $item['link'] }}"
                                       target="_blank"
                                       id="btn-{{ $item['id'] }}"
                                       onclick="markClicked('{{ $item['id'] }}')"
                                       class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800 transition-all">
                                        Enviar WhatsApp
                                        <svg class="w-4 h-4 ml-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8 text-center">
                        <a href="{{ route('envio-whatsapp.index') }}" class="text-blue-600 hover:underline">Iniciar novo envio</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Lógica de Controle de Cliques via LocalStorage --}}
    <script>
        // Função executada ao carregar a página
        document.addEventListener("DOMContentLoaded", function() {
            restoreState();
        });

        function markClicked(id) {
            // Salva no LocalStorage
            let clickedItems = JSON.parse(localStorage.getItem('whatsapp_tool_clicks')) || [];
            if (!clickedItems.includes(id)) {
                clickedItems.push(id);
                localStorage.setItem('whatsapp_tool_clicks', JSON.stringify(clickedItems));
            }

            // Atualiza visual
            applyVisualChange(id);
        }

        function applyVisualChange(id) {
            const btn = document.getElementById('btn-' + id);
            const card = document.getElementById('card-' + id);

            if (btn && card) {
                // Muda estilo do Botão
                btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'dark:bg-green-500');
                btn.classList.add('bg-gray-400', 'cursor-not-allowed', 'dark:bg-gray-600');
                btn.innerHTML = 'Enviado ✓';

                // Muda estilo do Card (Opcional, deixa mais claro)
                card.classList.add('opacity-60', 'bg-gray-100', 'dark:bg-gray-800');
            }
        }

        function restoreState() {
            let clickedItems = JSON.parse(localStorage.getItem('whatsapp_tool_clicks')) || [];
            clickedItems.forEach(id => {
                applyVisualChange(id);
            });
        }

        function resetHistory() {
            if(confirm('Isso limpará as marcações de "Enviado" apenas no seu navegador. Continuar?')) {
                localStorage.removeItem('whatsapp_tool_clicks');
                location.reload();
            }
        }
    </script>
</x-app-layout>
