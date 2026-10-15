<x-MundosDeMim::layout>
    <div class="mm-wrap mm-wrap-estreito mm-stack" style="max-width:960px;gap:32px">

        <div style="display:flex;flex-direction:column;gap:10px;max-width:700px">
            <h1 class="mm-h1">Seu retrato</h1>
            <p class="mm-lead">Quanto mais a gente sabe de você, mais os postais parecem você. Dá para mudar tudo quando quiser.</p>
        </div>

        @if(session('success'))
            <div role="status" class="mm-alerta mm-alerta-ok">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div role="alert" class="mm-alerta mm-alerta-erro">
                <b>Revise os campos abaixo:</b>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('mundos-de-mim.perfil.update') }}" method="POST" enctype="multipart/form-data" data-aguarde="Salvando seu retrato…" style="display:flex;flex-direction:column;gap:20px">
            @csrf

            <div class="mm-card" style="display:flex;flex-wrap:wrap;gap:24px;align-items:center">
                @if(isset($attributes->photo_path))
                    <img src="{{ Storage::url($attributes->photo_path) }}" alt="Sua foto atual" style="width:120px;height:120px;border-radius:50%;object-fit:cover;flex:none;border:3px solid var(--mm-bruma)">
                @else
                    <div aria-hidden="true" style="width:120px;height:120px;border-radius:50%;background:var(--mm-noite);border:2px dashed #6a64b0;display:flex;align-items:center;justify-content:center;color:var(--mm-bruma);font-size:13px;flex:none">Sua foto</div>
                @endif
                <div style="flex:1 1 280px;min-width:0;display:flex;flex-direction:column;gap:12px">
                    <h2 class="mm-display" style="margin:0;font-size:24px;font-weight:700">Foto de referência</h2>
                    <p style="margin:0;font-size:15px;line-height:1.5;color:var(--mm-texto-suave)">Uma foto de frente, com boa luz e o rosto à mostra.</p>
                    <input type="file" name="photo" id="photo" accept="image/*" class="mm-input" style="padding:12px" aria-label="Enviar nova foto">
                    @error('photo')<span class="mm-dica" style="color:#FFB09B">{{ $message }}</span>@enderror
                    @if(isset($attributes->photo_path))
                        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
                            <button type="button" id="btn-analyze-ia" class="mm-btn mm-btn-bruma">
                                <span id="analyze-spinner" class="mm-sr-only">Analisando...</span>
                                <span id="analyze-label">Sugerir meu retrato</span>
                            </button>
                            <span class="mm-dica" style="flex:1 1 220px">Preenchemos uma primeira versão do retrato e do estilo. Você revisa antes de salvar.</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mm-card" style="display:flex;flex-direction:column;gap:22px">
                <div class="mm-campo">
                    <label for="visual_profile">Como você se vê</label>
                    <span class="mm-dica">Esse é o campo mais importante. Tom de pele, olhos, cabelo, tatuagens, cicatrizes, manchas e o que mais faz você ser você.</span>
                    <textarea name="visual_profile" id="visual_profile" rows="6" required class="mm-input"
                              placeholder="Ex.: pele morena clara, olhos castanhos amendoados e cabelo cacheado escuro na altura dos ombros, sardas leves no rosto...">{{ old('visual_profile', $attributes->visual_profile ?? '') }}</textarea>
                    <span class="mm-dica">Escreva como se explicasse sua aparência para alguém que precisa te reconhecer em qualquer cenário.</span>
                </div>
                <div class="mm-campo">
                    <label for="gender_identity">Gênero com que você se identifica</label>
                    <input type="text" name="gender_identity" id="gender_identity" required class="mm-input"
                           value="{{ old('gender_identity', $attributes->gender_identity ?? '') }}"
                           placeholder="Ex.: mulher, homem, mulher trans, homem trans, não binárie, travesti...">
                    <span class="mm-dica">Ajuda os postais a respeitar sua identidade. A análise da foto não tenta adivinhar isso por você.</span>
                </div>
            </div>

            <div class="mm-card" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">

                <div class="mm-campo">
                    <label for="personality_vibe">Jeito, energia e presença</label>
                    <textarea name="personality_vibe" id="personality_vibe" rows="4" class="mm-input" placeholder="Ex.: Calmo, acolhedor, curioso, um pouco misterioso...">{{ old('personality_vibe', $attributes->personality_vibe ?? '') }}</textarea>
                    <span class="mm-dica">Ajuda a acertar expressão, postura e clima.</span>
                </div>

                <div class="mm-campo">
                    <label for="style_and_wardrobe">Estilo, roupas e estética</label>
                    <textarea name="style_and_wardrobe" id="style_and_wardrobe" rows="4" class="mm-input" placeholder="Ex.: Vestidos fluidos, tons terrosos, acessórios discretos...">{{ old('style_and_wardrobe', $attributes->style_and_wardrobe ?? '') }}</textarea>
                    <span class="mm-dica">Cores, acessórios, tecidos e referências.</span>
                </div>

                <div class="mm-campo">
                    <label for="interests_and_symbols">Gostos, interesses e símbolos</label>
                    <textarea name="interests_and_symbols" id="interests_and_symbols" rows="4" class="mm-input" placeholder="Ex.: Livros, gatos, fotografia, café, céu estrelado...">{{ old('interests_and_symbols', $attributes->interests_and_symbols ?? '') }}</textarea>
                    <span class="mm-dica">Hobbies, paixões, bichos, esportes e objetos que te representam.</span>
                </div>

                <div class="mm-campo">
                    <label for="favorite_settings">Cenários, lugares e atmosferas favoritas</label>
                    <textarea name="favorite_settings" id="favorite_settings" rows="4" class="mm-input" placeholder="Ex.: Florestas com neblina, cafés aconchegantes, cidades à noite...">{{ old('favorite_settings', $attributes->favorite_settings ?? '') }}</textarea>
                    <span class="mm-dica">Lugares, estações, luz, clima e hora do dia.</span>
                </div>

                <div class="mm-campo">
                    <label for="identity_details">Detalhes marcantes da sua identidade</label>
                    <textarea name="identity_details" id="identity_details" rows="4" class="mm-input" placeholder="Ex.: Meu time, cor favorita, profissão, animal, símbolos...">{{ old('identity_details', $attributes->identity_details ?? '') }}</textarea>
                    <span class="mm-dica">Tudo o que é muito seu.</span>
                </div>

                <div class="mm-campo">
                    <label for="avoid_in_generations">O que nunca deve aparecer</label>
                    <textarea name="avoid_in_generations" id="avoid_in_generations" rows="4" class="mm-input" placeholder="Ex.: Terror, neon, roupas muito formais...">{{ old('avoid_in_generations', $attributes->avoid_in_generations ?? '') }}</textarea>
                    <span class="mm-dica">Limites claros de estilo, cenário, cor ou clima.</span>
                </div>
            </div>

            <fieldset class="mm-card" style="border:0;margin:0;display:flex;flex-direction:column;gap:4px">
                <legend class="mm-display" style="padding:0;font-size:24px;font-weight:700;margin-bottom:8px">Receber o postal do dia</legend>
                <span class="mm-dica" style="margin-bottom:8px">Por onde você quer receber a arte de cada dia.</span>
                @php $preferencia = old('notification_preference', $attributes->notification_preference ?? 'none'); @endphp
                <label class="mm-opcao"><input type="radio" name="notification_preference" value="none" {{ $preferencia === 'none' ? 'checked' : '' }}> Não receber</label>
                <label class="mm-opcao"><input type="radio" name="notification_preference" value="email" {{ $preferencia === 'email' ? 'checked' : '' }}> E-mail</label>
                <label class="mm-opcao"><input type="radio" name="notification_preference" value="telegram" {{ $preferencia === 'telegram' ? 'checked' : '' }}> Telegram</label>
                <label class="mm-opcao" aria-disabled="true" title="Em breve"><input type="radio" name="notification_preference" value="whatsapp" disabled {{ $preferencia === 'whatsapp' ? 'checked' : '' }}> WhatsApp (em breve)</label>
            </fieldset>

            <div style="display:flex;justify-content:flex-end">
                <button type="submit" class="mm-btn mm-btn-primario" style="padding:16px 32px;font-size:17px">Salvar retrato</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('btn-analyze-ia')?.addEventListener('click', async function () {
            const btn = this;
            const rotulo = document.getElementById('analyze-label');
            const original = rotulo.textContent;

            btn.disabled = true;
            rotulo.textContent = 'Analisando sua foto...';
            window.mmAguarde('Analisando sua foto…');

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
                    ['visual_profile', 'style_and_wardrobe'].forEach(id => {
                        const element = document.getElementById(id);
                        if (!data[id] || !element) {
                            return;
                        }

                        element.value = data[id];
                        element.style.borderColor = '#7FE0C2';
                        setTimeout(() => { element.style.borderColor = ''; }, 2500);
                    });
                }
            } catch (error) {
                console.error(error);
                alert('Erro na comunicação com o servidor.');
            } finally {
                btn.disabled = false;
                rotulo.textContent = original;
                window.mmLiberar();
            }
        });
    </script>
</x-MundosDeMim::layout>
