<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Meu Perfil') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <p class="font-semibold mb-2">Revise os campos abaixo:</p>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('mundos-de-mim.perfil.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                        @csrf

                        <section class="border-b pb-8">
                            <div class="flex flex-col gap-2 mb-5">
                                <h3 class="text-lg font-medium text-gray-900">Foto de referência</h3>
                                <p class="text-sm text-gray-500">
                                    Envie uma foto nítida para a IA reconhecer seus traços e sugerir seu perfil visual.
                                </p>
                            </div>

                            <div class="flex flex-col md:flex-row md:items-center gap-6">
                                <div class="shrink-0">
                                    @if(isset($attributes->photo_path))
                                        <img class="h-28 w-28 object-cover rounded-full border-2 border-indigo-200"
                                             src="{{ Storage::url($attributes->photo_path) }}"
                                             alt="Foto atual">
                                    @else
                                        <div class="h-28 w-28 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700">Carregar nova foto</label>
                                    <p class="text-xs text-gray-500 mb-2">Prefira uma imagem bem iluminada, de frente e com o rosto visível.</p>
                                    <input type="file" name="photo" accept="image/*"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    @error('photo')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror

                                    @if(isset($attributes->photo_path))
                                        <div class="mt-4">
                                            <button type="button" id="btn-analyze-ia"
                                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                <span id="analyze-spinner" class="hidden mr-2 animate-spin">🌀</span>
                                                ✨ Sugerir perfil visual com IA
                                            </button>
                                            <p class="text-[11px] text-gray-500 mt-2">
                                                A IA preenche sugestões para o perfil visual e, quando possível, para estilo e roupas. Você revisa antes de salvar.
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section class="border-b pb-8">
                            <div class="mb-5">
                                <h3 class="text-lg font-medium text-gray-900">Perfil visual</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Este é o campo mais importante. Descreva tom de pele, olhos, cabelo, formato e comprimento do cabelo, traços únicos, manchas, cicatrizes, tatuagens e detalhes que fazem você ser você.
                                </p>
                            </div>

                            <label for="visual_profile" class="block font-medium text-sm text-gray-700 mb-2">Como você se descreveria visualmente?</label>
                            <textarea name="visual_profile" id="visual_profile" rows="7" required
                                      class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                      placeholder="Ex.: Tenho pele morena clara, olhos castanhos amendoados e cabelo cacheado escuro na altura dos ombros. Tenho sardas leves no rosto, sorriso marcante e costumo aparecer com brincos pequenos e roupas em tons terrosos.">{{ old('visual_profile', $attributes->visual_profile ?? '') }}</textarea>
                            <p class="text-xs text-gray-500 mt-2">
                                Dica: escreva como se estivesse explicando sua aparência para alguém que precisa te reconhecer em diferentes cenários.
                            </p>
                        </section>

                        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 border-b pb-8">
                            <div>
                                <label for="personality_vibe" class="block font-medium text-sm text-gray-700 mb-2">Jeito, energia e presença</label>
                                <textarea name="personality_vibe" id="personality_vibe" rows="5"
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                          placeholder="Ex.: Tenho uma energia calma e acolhedora, mas também criativa e curiosa. Gosto de um ar elegante, sonhador e um pouco misterioso.">{{ old('personality_vibe', $attributes->personality_vibe ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Ajuda a IA a acertar expressão, postura e clima emocional.</p>
                            </div>

                            <div>
                                <label for="interests_and_symbols" class="block font-medium text-sm text-gray-700 mb-2">Gostos, interesses e símbolos</label>
                                <textarea name="interests_and_symbols" id="interests_and_symbols" rows="5"
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                          placeholder="Ex.: Amo leitura, natureza, gatos, fotografia, fantasia, café, céu estrelado e trilhas sonoras suaves.">{{ old('interests_and_symbols', $attributes->interests_and_symbols ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Inclua hobbies, paixões, temas recorrentes, animais, esportes ou objetos que te representam.</p>
                            </div>

                            <div>
                                <label for="style_and_wardrobe" class="block font-medium text-sm text-gray-700 mb-2">Estilo, roupas e estética</label>
                                <textarea name="style_and_wardrobe" id="style_and_wardrobe" rows="5"
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                          placeholder="Ex.: Gosto de vestidos fluidos, tecidos leves, acessórios discretos, maquiagem natural e uma estética romântica com tons de verde, vinho e creme.">{{ old('style_and_wardrobe', $attributes->style_and_wardrobe ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Pode falar de paleta de cores, acessórios, maquiagem, tecidos e referências estéticas.</p>
                            </div>

                            <div>
                                <label for="favorite_settings" class="block font-medium text-sm text-gray-700 mb-2">Cenários, lugares e atmosferas favoritas</label>
                                <textarea name="favorite_settings" id="favorite_settings" rows="5"
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                          placeholder="Ex.: Amo me imaginar em florestas com neblina, cafés aconchegantes, cidades à noite, pôr do sol dourado, chuva leve e ambientes com luz suave.">{{ old('favorite_settings', $attributes->favorite_settings ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Fale de lugares, estações do ano, luz, clima, hora do dia e atmosfera.</p>
                            </div>

                            <div>
                                <label for="identity_details" class="block font-medium text-sm text-gray-700 mb-2">Detalhes marcantes da sua identidade</label>
                                <textarea name="identity_details" id="identity_details" rows="5"
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                          placeholder="Ex.: Meu time é o Bahia, adoro azul petróleo, meu animal favorito é a raposa, trabalho com design e sempre me identifico com elementos de lua, mar e cadernos.">{{ old('identity_details', $attributes->identity_details ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Aqui entram cor favorita, time favorito, profissão, símbolos, flores, objetos, artistas, filmes e referências que são muito suas.</p>
                            </div>

                            <div>
                                <label for="avoid_in_generations" class="block font-medium text-sm text-gray-700 mb-2">O que evitar nas gerações</label>
                                <textarea name="avoid_in_generations" id="avoid_in_generations" rows="5"
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                          placeholder="Ex.: Não gosto de estética neon, terror, exageros futuristas, roupas muito formais ou imagens com sensualização excessiva.">{{ old('avoid_in_generations', $attributes->avoid_in_generations ?? '') }}</textarea>
                                <p class="text-xs text-gray-500 mt-2">Use este espaço para colocar limites claros de estilo, cenário, cor, clima ou tipo de imagem.</p>
                            </div>
                        </section>

                        <section class="border-b pb-8">
                            <div class="mb-5">
                                <h3 class="text-lg font-medium text-gray-900">Dados complementares para temas legados</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Estes campos continuam existindo para compatibilidade com prompts e regras antigas, mas agora são opcionais.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="height" class="block font-medium text-sm text-gray-700">Altura (cm)</label>
                                    <input type="number" name="height" id="height"
                                           value="{{ old('height', $attributes->height ?? '') }}"
                                           class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                                </div>

                                <div>
                                    <label for="weight" class="block font-medium text-sm text-gray-700">Peso (kg)</label>
                                    <input type="number" name="weight" id="weight" step="0.1"
                                           value="{{ old('weight', $attributes->weight ?? '') }}"
                                           class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                                </div>

                                <div>
                                    <label for="body_type" class="block font-medium text-sm text-gray-700">Tipo de corpo</label>
                                    <input type="text" name="body_type" id="body_type"
                                           value="{{ old('body_type', $attributes->body_type ?? '') }}"
                                           class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                           placeholder="Ex.: atlético, curvilíneo, magro, forte">
                                </div>

                                <div>
                                    <label for="eye_color" class="block font-medium text-sm text-gray-700">Cor dos olhos</label>
                                    <input type="text" name="eye_color" id="eye_color"
                                           value="{{ old('eye_color', $attributes->eye_color ?? '') }}"
                                           class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                           placeholder="Ex.: castanhos, verdes, mel">
                                </div>

                                <div>
                                    <label for="hair_type" class="block font-medium text-sm text-gray-700">Cabelo</label>
                                    <input type="text" name="hair_type" id="hair_type"
                                           value="{{ old('hair_type', $attributes->hair_type ?? '') }}"
                                           class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                           placeholder="Ex.: cacheado castanho escuro, liso curto preto">
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-5">
                                <h3 class="text-lg font-medium text-gray-900">Receber a foto do dia</h3>
                                <p class="text-sm text-gray-500 mt-1">Escolha por onde deseja receber sua arte gerada diariamente.</p>
                            </div>

                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" name="notification_preference" value="none"
                                        {{ old('notification_preference', $attributes->notification_preference ?? 'none') === 'none' ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Não receber</span>
                                </label>

                                <label class="flex items-center">
                                    <input type="radio" name="notification_preference" value="email"
                                        {{ old('notification_preference', $attributes->notification_preference ?? 'none') === 'email' ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700"><i class="fa-solid fa-envelope"></i> E-mail</span>
                                </label>

                                <label class="flex items-center">
                                    <input type="radio" name="notification_preference" value="telegram"
                                        {{ old('notification_preference', $attributes->notification_preference ?? 'none') === 'telegram' ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700"><i class="fa-brands fa-telegram"></i> Telegram</span>
                                </label>

                                <label class="flex items-center opacity-50 cursor-not-allowed" title="Em breve">
                                    <input type="radio" name="notification_preference" value="whatsapp" disabled
                                        {{ old('notification_preference', $attributes->notification_preference ?? 'none') === 'whatsapp' ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700"><i class="fa-brands fa-whatsapp"></i> WhatsApp (em breve)</span>
                                </label>
                            </div>
                        </section>

                        <div class="pt-2">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Salvar meu perfil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btn-analyze-ia')?.addEventListener('click', async function() {
            const btn = this;
            const spinner = document.getElementById('analyze-spinner');

            btn.disabled = true;
            spinner.classList.remove('hidden');

            try {
                const response = await fetch("{{ route('mundos-de-mim.perfil.analyze') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.error) {
                    alert('Erro: ' + data.error);
                } else {
                    const fields = ['visual_profile', 'style_and_wardrobe', 'body_type', 'eye_color', 'hair_type'];

                    fields.forEach(id => {
                        if (!data[id]) {
                            return;
                        }

                        const element = document.getElementById(id);
                        if (!element) {
                            return;
                        }

                        element.value = data[id];
                        element.classList.add('bg-green-50', 'border-green-500');
                        setTimeout(() => element.classList.remove('bg-green-50', 'border-green-500'), 2000);
                    });
                }
            } catch (error) {
                console.error(error);
                alert('Erro na comunicação com o servidor.');
            } finally {
                btn.disabled = false;
                spinner.classList.add('hidden');
            }
        });
    </script>
</x-MundosDeMim::layout>
