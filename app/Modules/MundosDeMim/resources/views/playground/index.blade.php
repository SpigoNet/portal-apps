<x-MundosDeMim::layout>
    <div class="mm-wrap mm-stack" style="gap:32px">

        <div style="display:flex;flex-direction:column;gap:10px;max-width:700px">
            <h1 class="mm-h1">Ateliê</h1>
            <p class="mm-lead">Teste prompts, refine o texto e veja o resultado antes de publicar em um mundo.</p>
        </div>

        @if($currentUser->can('admin-do-app') && $allUsers->isNotEmpty())
            <form action="{{ route('mundos-de-mim.playground.select-user') }}" method="POST" class="mm-card" style="display:flex;flex-wrap:wrap;gap:12px 20px;align-items:center;padding:20px 24px">
                @csrf
                <label for="user_id" class="mm-rotulo-campo" style="font-size:15px">Testar como</label>
                <select name="user_id" id="user_id" onchange="this.form.submit()" class="mm-input" style="width:auto;flex:1 1 260px;max-width:460px">
                    @foreach($allUsers as $u)
                        <option value="{{ $u->id }}" {{ $u->id === $targetUser->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
                <span class="mm-chip mm-chip-ok">{{ max(0, $targetUser->credits) }} {{ $targetUser->credits === 1 ? 'extra' : 'extras' }}</span>
            </form>
        @endif

        @if(session('error'))
            <div role="alert" class="mm-alerta mm-alerta-erro"><b>Algo deu errado:</b> {{ session('error') }}</div>
        @endif

        <form id="form-gerar" data-aguarde="Gerando o postal… pode levar alguns instantes." action="{{ route('mundos-de-mim.playground.generate') }}" method="POST" style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
            @csrf

            <div style="flex:1 1 340px;min-width:0;display:flex;flex-direction:column;gap:24px">

                <section class="mm-card" style="display:flex;flex-direction:column;gap:14px">
                    <span class="mm-rotulo">1 · Referência</span>
                    @if($hasPhoto)
                        <div style="display:flex;gap:16px;align-items:center">
                            <img src="{{ Storage::url($attributes->photo_path) }}" alt="Foto de referência atual" style="width:88px;height:88px;border-radius:20px;object-fit:cover;border:3px solid var(--mm-bruma);flex:none">
                            <div style="display:flex;flex-direction:column;gap:6px">
                                <span class="mm-chip mm-chip-ok" style="align-self:flex-start">Foto carregada</span>
                                <span class="mm-dica">Enviada junto com o retrato e o prompt para manter a identidade.</span>
                            </div>
                        </div>
                    @else
                        <div class="mm-alerta mm-alerta-erro">
                            Sem foto de referência. Complete o <a href="{{ route('mundos-de-mim.perfil.index') }}" style="text-decoration:underline;font-weight:600">retrato</a> para gerar imagens aqui.
                        </div>
                    @endif
                </section>

                <section class="mm-card" style="display:flex;flex-direction:column;gap:14px">
                    <span class="mm-rotulo">2 · Modelo de IA</span>
                    <div class="mm-campo">
                        <label for="ai_provider_id" class="mm-sr-only">Provedor e modelo</label>
                        <select name="ai_provider_id" id="ai_provider_id" class="mm-input">
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ (string) old('ai_provider_id', optional($selectedProvider)->id) === (string) $provider->id ? 'selected' : '' }}>
                                    {{ $provider->name }} ({{ $provider->driver }} / {{ $provider->model }})
                                </option>
                            @endforeach
                        </select>
                        @if($providers->isEmpty())
                            <span class="mm-dica" style="color:#FFB09B">Nenhum provedor ativo encontrado.</span>
                        @endif
                    </div>

                    @if($currentUser->can('admin-do-app'))
                        <label class="mm-opcao" style="padding:4px 0">
                            <input type="checkbox" name="send_to_user" value="1">
                            <span>Enviar o resultado para o usuário<br><span class="mm-dica">Manda a imagem por e-mail para a pessoa escolhida acima.</span></span>
                        </label>
                    @endif
                </section>

                @if($prompts->isNotEmpty())
                    <section class="mm-card" style="display:flex;flex-direction:column;gap:14px">
                        <span class="mm-rotulo">Prompts cadastrados</span>
                        <div class="mm-campo">
                            <label for="prompt_id" class="mm-sr-only">Escolher um prompt cadastrado</label>
                            <select name="prompt_id" id="prompt_id" onchange="loadPrompt(this.value)" class="mm-input">
                                <option value="">Escolha um prompt...</option>
                                @foreach($prompts as $prompt)
                                    <option value="{{ $prompt->id }}" data-prompt="{{ $prompt->prompt_text }}">{{ $prompt->theme->name ?? 'Sem mundo' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="max-height:460px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;padding-right:4px;-webkit-overflow-scrolling:touch">
                            @foreach($prompts as $promptItem)
                                @php
                                    $promptExample = $promptItem->generatedExamples->first();
                                    $themeExample = optional($promptItem->theme)->examples->first();
                                    $previewImage = $promptExample ? $promptExample->image_path : ($themeExample ? $themeExample->image_path : null);
                                    $previewUrl = $previewImage ? Storage::url($previewImage) : null;
                                @endphp
                                <button type="button" onclick="applyPromptFromList('{{ $promptItem->id }}', @js($promptItem->prompt_text))"
                                        style="display:flex;flex:none;gap:12px;align-items:flex-start;text-align:left;padding:10px;border:1px solid var(--mm-linha);border-radius:16px;background:var(--mm-noite);color:inherit;cursor:pointer;font-family:inherit;width:100%;height:auto;overflow:hidden">
                                    @if($previewUrl)
                                        <img src="{{ $previewUrl }}" alt="" loading="lazy" style="width:72px;height:96px;object-fit:cover;border-radius:10px;flex:none">
                                    @else
                                        <span class="mm-cena-{{ ($promptItem->id % 4) + 1 }}" aria-hidden="true" style="width:72px;height:96px;border-radius:10px;flex:none"></span>
                                    @endif
                                    <span style="min-width:0;flex:1 1 auto;display:flex;flex-direction:column;gap:4px">
                                        <b style="font-size:15px;color:var(--mm-bruma)">{{ $promptItem->theme->name ?? 'Sem mundo' }}</b>
                                        <span style="font-size:13px;line-height:1.45;color:var(--mm-texto-suave);display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden">{{ $promptItem->prompt_text }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <section class="mm-card" style="flex:2 1 460px;min-width:0;display:flex;flex-direction:column;gap:16px">
                <div style="display:flex;flex-wrap:wrap;gap:10px 16px;justify-content:space-between;align-items:center">
                    <label for="prompt" class="mm-rotulo" style="margin:0">3 · Prompt</label>
                    <span class="mm-chip mm-chip-ok">{{ max(0, $targetUser->credits) }} {{ $targetUser->credits === 1 ? 'extra disponível' : 'extras disponíveis' }}</span>
                </div>

                <textarea name="prompt" id="prompt" rows="9" required class="mm-input"
                          placeholder="Descreva a cena. Ex.: retrato cinematográfico em uma cidade que acende ao amanhecer, luz dourada, muito detalhe">{{ old('prompt') ?? ($latestPrompt ? $latestPrompt->prompt_text : '') }}</textarea>

                <div style="display:flex;flex-wrap:wrap;gap:10px">
                    <button type="button" id="btn-magic-wand" class="mm-btn mm-btn-bruma">
                        <span id="magic-spinner" class="hidden">Refinando...</span>
                        <span id="magic-icon"><i class="fa-solid fa-wand-magic-sparkles"></i> Refinar o texto</span>
                    </button>
                    <button type="button" id="btn-preview-prompt" class="mm-btn mm-btn-contorno">
                        <span id="preview-spinner" class="hidden">Montando...</span>
                        <span id="preview-icon"><i class="fa-solid fa-eye"></i> Ver o que vai para a IA</span>
                    </button>
                </div>

                <div id="prompt-preview-panel" class="hidden" style="border:1px solid var(--mm-linha);border-radius:18px;background:var(--mm-noite)">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:14px 16px;border-bottom:1px solid var(--mm-linha)">
                        <div>
                            <b>Texto final enviado para a IA</b>
                            <p id="prompt-preview-note" class="mm-dica" style="margin:4px 0 0">Esta prévia usa a mesma montagem aplicada na geração.</p>
                        </div>
                        <button type="button" id="btn-close-preview" aria-label="Fechar prévia" style="background:none;border:0;color:var(--mm-texto-suave);cursor:pointer;padding:8px;font-size:18px"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <pre id="prompt-preview-content" style="margin:0;padding:16px;white-space:pre-wrap;word-break:break-word;font:14px/1.6 'Instrument Sans',sans-serif;color:#D6D3EE"></pre>
                </div>

                <div style="display:flex;justify-content:flex-end;padding-top:8px;border-top:1px solid var(--mm-linha)">
                    <button type="submit" id="btn-gerar" {{ $providers->isEmpty() ? 'disabled' : '' }} class="mm-btn mm-btn-primario" style="padding:16px 32px;font-size:17px">Gerar postal de teste</button>
                </div>
            </section>
        </form>

        @if(session('result'))
            <section class="mm-card mm-card-lua" style="display:flex;flex-direction:column;gap:16px">
                <h2 class="mm-display" style="margin:0;font-size:28px;font-weight:700">Resultado</h2>
                <div style="width:100%;overflow-x:auto">{!! session('result') !!}</div>
            </section>
        @endif

        @if(isset($recentGenerations) && $recentGenerations->isNotEmpty())
            <section style="display:flex;flex-direction:column;gap:20px">
                <div>
                    <h2 class="mm-display" style="margin:0;font-size:32px;font-weight:700">Testes recentes</h2>
                    <p class="mm-dica" style="margin:6px 0 0">Toque em um para reaproveitar o prompt.</p>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px">
                    @foreach($recentGenerations as $generation)
                        <button type="button" onclick="applyPromptFromList('{{ $generation->prompt_id ?? '' }}', @js($generation->final_prompt_used))"
                                style="text-align:left;padding:0;border:0;background:var(--mm-crepusculo);color:inherit;border-radius:20px;overflow:hidden;cursor:pointer;font-family:inherit;display:flex;flex-direction:column">
                            <span class="mm-postal" style="border:0;border-radius:0;aspect-ratio:4/3;display:block"><img src="{{ $generation->image_url }}" alt="Resultado gerado" loading="lazy"></span>
                            <span style="padding:14px;display:flex;flex-direction:column;gap:6px">
                                <span class="mm-dica">{{ optional($generation->created_at)->format('d/m/Y H:i') }}</span>
                                <span style="font-size:14px;line-height:1.45;color:#D6D3EE;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">{{ $generation->final_prompt_used }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
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

        document.getElementById('btn-close-preview')?.addEventListener('click', function () {
            document.getElementById('prompt-preview-panel')?.classList.add('hidden');
        });

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
                    promptArea.style.borderColor = '#7FE0C2';
                    setTimeout(() => { promptArea.style.borderColor = ''; }, 1500);
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

        document.getElementById('btn-preview-prompt')?.addEventListener('click', async function () {
            const promptArea = document.getElementById('prompt');
            const btn = this;
            const spinner = document.getElementById('preview-spinner');
            const icon = document.getElementById('preview-icon');
            const panel = document.getElementById('prompt-preview-panel');
            const content = document.getElementById('prompt-preview-content');
            const note = document.getElementById('prompt-preview-note');

            if (!promptArea.value.trim()) {
                alert('Digite um prompt primeiro para eu montar a prévia.');
                return;
            }

            btn.disabled = true;
            spinner.classList.remove('hidden');
            icon.classList.add('hidden');

            try {
                const response = await fetch("{{ route('mundos-de-mim.playground.preview') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        prompt: promptArea.value
                    })
                });

                const data = await response.json();

                if (data.error) {
                    alert('Erro: ' + data.error);
                    return;
                }

                content.textContent = data.preview || '';
                note.textContent = data.has_photo
                    ? 'Esta prévia usa a mesma montagem aplicada na geração e considera a foto de referência atual.'
                    : 'A prévia foi montada, mas ainda falta uma foto de referência válida para gerar a imagem.';
                panel.classList.remove('hidden');
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
