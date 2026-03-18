<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laboratório de Criação (Playground)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if($currentUser->can('admin-do-app') && $allUsers->isNotEmpty())
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
                    <form action="{{ route('mundos-de-mim.playground.select-user') }}" method="POST" class="flex items-center gap-4">
                        @csrf
                        <label class="text-sm font-bold text-indigo-700">Testar como usuário:</label>
                        <select name="user_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" {{ $u->id === $targetUser->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                        <span class="text-xs text-indigo-600">
                            <i class="fa-solid fa-coins"></i> {{ $targetUser->credits }} créditos
                        </span>
                    </form>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                    <strong>Erro:</strong> {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-8 border-b pb-6">
                        <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                            <span>📸</span> 1. Imagem de Referência (Opcional)
                        </h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Se houver foto, ela será enviada para análise (Gemini) ou usada como base (Pollinations).
                        </p>

                        @if($hasPhoto)
                            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <img src="{{ Storage::url($attributes->photo_path) }}" alt="Foto de Referência"
                                    class="h-24 w-24 object-cover rounded-lg shadow-sm border-2 border-indigo-200">
                                <div>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Imagem Carregada
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1 break-all">{{ $attributes->photo_path }}</p>
                                </div>
                            </div>
                        @else
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                                <p class="text-yellow-700 text-sm">
                                    <strong>Sem foto:</strong> Configure seu <a
                                        href="{{ route('mundos-de-mim.perfil.index') }}" class="underline font-bold">Perfil
                                        Biométrico</a> para testar recursos visuais.
                                </p>
                            </div>
                        @endif
                    </div>

                    <form action="{{ route('mundos-de-mim.playground.generate') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="col-span-1 space-y-4">
                                <div>
                                    <label for="ai_provider_id" class="block text-sm font-bold text-gray-700 mb-2">2. Provedor / Modelo</label>
                                    <select name="ai_provider_id" id="ai_provider_id"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($providers as $provider)
                                            <option value="{{ $provider->id }}"
                                                {{ (string) old('ai_provider_id', optional($selectedProvider)->id) === (string) $provider->id ? 'selected' : '' }}>
                                                {{ $provider->name }} ({{ $provider->driver }} / {{ $provider->model }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($providers->isEmpty())
                                        <p class="text-xs text-red-500 mt-2 font-semibold">
                                            Nenhum provedor ativo encontrado.
                                        </p>
                                    @endif
                                </div>

                                @if($prompts->isNotEmpty())
                                <div>
                                    <label for="prompt_id" class="block text-sm font-bold text-gray-700 mb-2">Ou use um Prompt Cadastrado</label>
                                    <select name="prompt_id" id="prompt_id" onchange="loadPrompt(this.value)"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Selecione --</option>
                                        @foreach($prompts as $prompt)
                                            <option value="{{ $prompt->id }}" data-prompt="{{ $prompt->prompt_text }}">
                                                {{ $prompt->theme->name ?? 'Sem tema' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="max-h-80 overflow-y-auto space-y-2 pr-1">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Lista de prompts para gerar</p>
                                    @foreach($prompts as $promptItem)
                                        @php
                                            $promptExample = $promptItem->generatedExamples->first();
                                            $themeExample = optional($promptItem->theme)->examples->first();
                                            
                                            $previewImage = $promptExample ? $promptExample->image_path : ($themeExample ? $themeExample->image_path : null);
                                            $previewUrl = $previewImage ? Storage::url($previewImage) : null;
                                        @endphp
                                        <button type="button"
                                                onclick="applyPromptFromList('{{ $promptItem->id }}', @js($promptItem->prompt_text))"
                                                class="w-full text-left p-3 border border-gray-200 rounded-xl hover:border-[#62A87C] hover:bg-[#62A87C]/5 transition group">
                                            <div class="flex gap-4 items-start">
                                                @if($previewUrl)
                                                    <img src="{{ $previewUrl }}" class="h-20 w-20 object-cover rounded-lg border border-gray-200 shadow-sm group-hover:scale-105 transition-transform" alt="Resultado">
                                                @else
                                                    <div class="h-20 w-20 rounded-lg border border-gray-200 bg-gray-50 flex flex-col items-center justify-center text-gray-400">
                                                        <i class="fa-solid fa-image text-xl mb-1"></i>
                                                        <span class="text-[9px] uppercase tracking-wider">Sem Img</span>
                                                    </div>
                                                @endif
                                                <div class="min-w-0 flex-1 pt-1">
                                                    <p class="text-sm font-bold text-[#7B2CBF] truncate mb-1">{{ $promptItem->theme->name ?? 'Sem tema' }}</p>
                                                    <p class="text-xs text-gray-600 line-clamp-3 leading-relaxed">{{ $promptItem->prompt_text }}</p>
                                                </div>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                                @endif

                                @if($currentUser->can('admin-do-app'))
                                <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-200">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="send_to_user" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-indigo-700 font-medium">
                                            <i class="fa-solid fa-paper-plane"></i> Enviar para o usuário
                                        </span>
                                    </label>
                                    <p class="text-xs text-indigo-600 mt-1">Envia a imagem gerada por email para o usuário selecionado</p>
                                </div>
                                @endif
                            </div>

                            <div class="col-span-2">
                                <div class="flex justify-between items-center mb-2">
                                    <label for="prompt" class="block text-sm font-bold text-gray-700">3. Prompt
                                        (Comando)</label>

                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center gap-1.5 px-3 py-1 bg-indigo-50 rounded-full text-indigo-700 text-xs font-bold border border-indigo-100">
                                            <i class="fa-solid fa-coins"></i> {{ $targetUser->credits }} Créditos
                                        </div>

                                        <button type="button" id="btn-magic-wand"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full text-white text-xs font-bold hover:shadow-md transition">
                                            <span id="magic-spinner" class="hidden animate-spin">🌀</span>
                                            <i id="magic-icon" class="fa-solid fa-wand-magic-sparkles"></i>
                                            Refinar (Varinha Mágica)
                                        </button>
                                    </div>
                                </div>

                                <textarea name="prompt" id="prompt" rows="5"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Ex: Cyberpunk portrait of a hero, cinematic lighting, ultra detailed"
                                    required>{{ old('prompt') ?? ($latestPrompt ? $latestPrompt->prompt_text : '') }}</textarea>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <button type="submit"
                                {{ $providers->isEmpty() ? 'disabled' : '' }}
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded shadow-lg transition-all transform hover:scale-105">
                                Executar Teste 🚀
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            @if(session('result'))
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-indigo-500">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Resultado da Geração:</h3>

                        <div class="w-full">
                            {!! session('result') !!}
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($recentGenerations) && $recentGenerations->isNotEmpty())
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Resultados Recentes (clique para reutilizar prompt)</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($recentGenerations as $generation)
                                <button type="button"
                                        onclick="applyPromptFromList('{{ $generation->prompt_id ?? '' }}', @js($generation->final_prompt_used))"
                                        class="text-left border rounded-lg overflow-hidden hover:border-indigo-300 transition bg-white">
                                    <div class="aspect-video bg-gray-100">
                                        <img src="{{ $generation->image_url }}" class="w-full h-full object-cover" alt="Resultado gerado">
                                    </div>
                                    <div class="p-3">
                                        <p class="text-xs text-gray-500 mb-1">{{ optional($generation->created_at)->format('d/m/Y H:i') }}</p>
                                        <p class="text-sm text-gray-700 line-clamp-3">{{ $generation->final_prompt_used }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        function loadPrompt(promptId) {
            if (!promptId) return;
            
            const select = document.getElementById('prompt_id');
            const option = select.options[select.selectedIndex];
            const promptText = option.getAttribute('data-prompt');
            
            if (promptText) {
                document.getElementById('prompt').value = promptText;
            }
        }

        function applyPromptFromList(promptId, promptText) {
            if (promptId) {
                const select = document.getElementById('prompt_id');
                if (select) {
                    select.value = promptId;
                }
            }

            if (promptText) {
                const promptArea = document.getElementById('prompt');
                promptArea.value = promptText;
                promptArea.focus();
            }
        }

        document.getElementById('btn-magic-wand')?.addEventListener('click', async function () {
            const promptArea = document.getElementById('prompt');
            const btn = this;
            const spinner = document.getElementById('magic-spinner');
            const icon = document.getElementById('magic-icon');

            if (!promptArea.value.trim()) {
                alert('Digite algo primeiro para eu poder refinar!');
                return;
            }

            btn.disabled = true;
            spinner.classList.remove('hidden');
            icon.classList.add('hidden');

            try {
                const response = await fetch("{{ route('mundos-de-mim.playground.refine') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        prompt: promptArea.value,
                        ai_provider_id: document.getElementById('ai_provider_id')?.value || null
                    })
                });

                const data = await response.json();

                if (data.error) {
                    alert('Erro: ' + data.error);
                } else if (data.refined) {
                    promptArea.value = data.refined;
                    promptArea.classList.add('bg-indigo-50');
                    setTimeout(() => promptArea.classList.remove('bg-indigo-50'), 1000);
                }
            } catch (error) {
                console.error(error);
                alert('Erro na comunicação com o servidor.');
            } finally {
                btn.disabled = false;
                spinner.classList.add('hidden');
                icon.classList.remove('hidden');
            }
        });
    </script>
</x-MundosDeMim::layout>