<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laboratório de Criação (Playground)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

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
                            <div class="col-span-1">
                                <label for="driver" class="block text-sm font-bold text-gray-700 mb-2">2. Escolha a
                                    IA</label>
                                <select name="driver" id="driver"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="gemini" {{ old('driver') == 'gemini' ? 'selected' : '' }}>
                                        Gemini 2.0 (Texto/Análise)
                                    </option>
                                    <option value="pollination" {{ old('driver') == 'pollination' ? 'selected' : '' }}>
                                        Pollinations (Gerar Imagem)
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-2">
                                    <strong>Gemini:</strong> Descreve ou conversa.<br>
                                    <strong>Pollinations:</strong> Cria novas imagens.
                                </p>
                            </div>

                            <div class="col-span-2">
                                <div class="flex justify-between items-center mb-2">
                                    <label for="prompt" class="block text-sm font-bold text-gray-700">3. Prompt
                                        (Comando)</label>

                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center gap-1.5 px-3 py-1 bg-indigo-50 rounded-full text-indigo-700 text-xs font-bold border border-indigo-100">
                                            <i class="fa-solid fa-coins"></i> {{ auth()->user()->credits }} Créditos
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
                                    placeholder="Ex para Gemini: O que você vê nesta foto?&#10;Ex para Pollinations: Cyberpunk portrait of a hero..."
                                    required>{{ old('prompt') ?? "Source my Image, keep face 99.9% Orginal, The focus of the 8K is on the face and hairstyle at 100%, as a reference for the uploaded image, a super-clear cinematic portrait photo, wearing the full white kit of the iranian national team, decorated with the club's logo and the sponsor's 'Emirates Fly Better' in black, with the Champions League logo on the sleeve. The movement and atmosphere: A showy move where he balances a football on his right finger; the atmosphere is festive and cheerful, expressing skill and confidence.  
The location and resolution: A bright green grass in the background suggests being on the pitch; the image is technically modified with very high clarity and saturated colors, highlighting the details of the ball and the shirt.
The image size is 9/16." }}</textarea>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <button type="submit"
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

        </div>
    </div>

    <script>
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
                    body: JSON.stringify({ prompt: promptArea.value })
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