<x-yomi::layout title="Configurações" :active="null">
    <style>
        .settings-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;align-items:start}
        .settings-panel h2{font-size:18px;margin:0 0 4px}
        .settings-panel .panel-note{color:var(--muted);font-size:12px;margin:0 0 14px}
        .provider-card{background:var(--surface-2);border:1px solid var(--line);border-radius:7px;padding:13px;margin-bottom:10px}
        .provider-top{display:flex;align-items:center;justify-content:space-between;gap:12px}
        .provider-top strong{display:block;font-size:13px}
        .provider-top small{color:var(--muted);font-size:10px}
        .provider-desc{color:var(--ghost);font-size:11px;margin:6px 0 0;line-height:1.5}
        .provider-card .field{margin-top:12px}
        .field{display:block}
        .field span{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
        .field input{width:100%;background:var(--surface);border:1px solid var(--line);border-radius:6px;color:var(--paper);padding:10px 12px;font:inherit;font-size:12px}
        .field input:focus{outline:none;border-color:var(--red)}
        .field small{display:block;color:var(--ghost);font-size:10px;margin-top:5px}
        .switch{display:inline-flex;align-items:center;gap:8px;cursor:pointer}
        .switch input{display:none}
        .switch .track{width:42px;height:22px;border-radius:20px;background:#34323a;position:relative;transition:.18s}
        .switch .track::after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#8d8b93;transition:.18s}
        .switch input:checked + .track{background:var(--red)}
        .switch input:checked + .track::after{left:23px;background:#2d0b0e}
        .switch .state{font:700 11px 'Space Grotesk';color:var(--muted);min-width:52px}
        .priority-list{display:flex;flex-direction:column;gap:8px}
        .priority-row{display:flex;align-items:center;gap:12px;background:var(--surface-2);border:1px solid var(--line);border-radius:7px;padding:11px 13px}
        .priority-grip{color:var(--ghost);cursor:grab;font-size:15px}
        .priority-name{flex:1;font-size:13px}
        .priority-name small{display:block;color:var(--muted);font-size:10px;margin-top:2px}
        .priority-actions{display:flex;gap:6px}
        .priority-actions button{width:30px;height:30px;border-radius:6px;border:1px solid var(--line);background:var(--surface);color:var(--paper);font:700 13px 'Space Grotesk';cursor:pointer}
        .priority-actions button:hover:not(:disabled){border-color:var(--red);color:var(--red)}
        .priority-actions button:disabled{opacity:.35;cursor:default}
        .health-row{display:flex;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap}
        .health-btn{display:inline-flex;align-items:center;gap:7px;background:var(--surface);border:1px solid var(--line);border-radius:6px;color:var(--paper);font:600 11px 'Space Grotesk';padding:8px 12px;cursor:pointer}
        .health-btn:hover:not(:disabled){border-color:var(--red);color:var(--red)}
        .health-btn:disabled{opacity:.6;cursor:wait}
        .health-status{font:700 10px 'Space Grotesk';padding:5px 9px;border-radius:20px;letter-spacing:.05em}
        .health-status.ok{background:rgba(46,204,113,.14);color:#4ade80}
        .health-status.error{background:rgba(239,68,68,.14);color:#f87171}
        .health-status.not_configured{background:rgba(250,204,21,.14);color:#facc15}
        .health-status.checking{background:rgba(148,163,184,.14);color:#cbd5e1}
        .health-msg{color:var(--ghost);font-size:10px;flex-basis:100%}
        .settings-save{margin-top:16px}
        .settings-empty{color:var(--muted);font-size:12px}
        @media(max-width:1100px){.settings-grid{grid-template-columns:1fr}}
    </style>

    <div class="page-head">
        <div>
            <div class="eyebrow">● ADMINISTRAÇÃO · PROVEDORES</div>
            <h1>Configurações</h1>
            <p>Defina quais APIs fornecem metadados ao Yomi, em qual ordem são consultadas e suas credenciais.</p>
        </div>
    </div>

    @if($errors->any())<div class="flash" style="background:var(--red);color:#230c0e">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('yomi.settings.update') }}"
          x-data="{
              order: {{ Js::from($priority) }},
              query: '',
              results: {},
              filtered(provider) {
                  if (this.query === '') return provider;
                  return provider.toLowerCase().includes(this.query.toLowerCase());
              },
              move(index, delta) {
                  const target = index + delta;
                  if (target < 0 || target >= this.order.length) return;
                  const [item] = this.order.splice(index, 1);
                  this.order.splice(target, 0, item);
              },
              checkHealth(provider) {
                  this.results[provider] = { checking: true, status: 'checking', message: null };
                  const url = '{{ $healthUrl }}'.replace('__PROVIDER__', provider);
                  fetch(url)
                      .then(response => response.json())
                      .then(data => { this.results[provider] = { checking: false, ...data }; })
                      .catch(() => {
                          this.results[provider] = { checking: false, status: 'error', message: 'Falha de conexão ao verificar o provedor.' };
                      });
              },
              hasResult(provider) {
                  return this.results[provider] !== undefined;
              },
              isChecking(provider) {
                  const result = this.results[provider];
                  return result !== undefined && result.checking === true;
              },
              healthLabel(result) {
                  if (result.status === 'ok') return 'ONLINE';
                  if (result.status === 'error') return 'FALHA';
                  if (result.status === 'not_configured') return 'SEM CONFIG';
                  if (result.status === 'checking') return 'VERIFICANDO';
                  return 'INVÁLIDO';
              },
              healthText(result) {
                  if (result.message) return result.message;
                  if (result.status === 'checking') return 'Verificando conectividade...';
                  return result.latency_ms != null ? 'Latência: ' + result.latency_ms + 'ms' : '';
              },
          }">
        @csrf

        <div class="settings-grid">
            <section class="panel settings-panel">
                <h2>Provedores de metadados</h2>
                <p class="panel-note">Desative um provedor para deixar de consultá-lo em buscas, destaques e sincronizações.</p>

                @foreach($providers as $name => $config)
                    @php
                        $label = $labels[$name] ?? $name;
                        $fields = $credentialFields[$name] ?? [];
                    @endphp
                    <div class="provider-card">
                        <div class="provider-top">
                            <div>
                                <strong>{{ $label }}</strong>
                                <small>{{ $name }} · {{ $config['enabled'] ? 'ativo' : 'inativo' }}</small>
                            </div>
                            <label class="switch" x-data="{ on: {{ $config['enabled'] ? 'true' : 'false' }} }">
                                <input type="checkbox" name="providers[{{ $name }}][enabled]" value="1" x-model="on">
                                <span class="track"></span>
                                <span class="state" x-text="on ? 'ATIVO' : 'INATIVO'"></span>
                            </label>
                        </div>
                        <p class="provider-desc">{{ $descriptions[$name] ?? '' }}</p>
                        <label class="field">
                            <span>Base URL da API</span>
                            <input type="url" name="providers[{{ $name }}][base_url]"
                                   value="{{ $config['base_url'] }}"
                                   placeholder="{{
                                       $name === 'jikan' ? 'https://api.jikan.moe/v4'
                                       : ($name === 'anilist' ? 'https://graphql.anilist.co'
                                       : ($name === 'kitsu' ? 'https://kitsu.io/api/edge'
                                       : ($name === 'mal' ? 'https://api.myanimelist.net/v2'
                                       : 'https://api.mangadex.org')))
                                   }}">
                            <small>Deixe vazio para usar a URL padrão.</small>
                        </label>

                        @foreach($fields as $key => $spec)
                            <label class="field">
                                <span>{{ $spec['label'] }}@if($spec['required']) *@endif</span>
                                <input type="text" name="providers[{{ $name }}][credentials][{{ $key }}]"
                                       value="{{ $config['credentials'][$key] ?? '' }}"
                                       autocomplete="off" spellcheck="false">
                                <small>{{ $spec['help'] }}</small>
                            </label>
                        @endforeach

                        <div class="health-row">
                            <button type="button" class="health-btn"
                                    x-data="{ provider: '{{ $name }}' }"
                                    @click="checkHealth(provider)"
                                    x-bind:disabled="isChecking(provider)">
                                <span x-show="!isChecking('{{ $name }}')">⟳</span>
                                <span x-show="isChecking('{{ $name }}')">…</span>
                                Verificar estado de saúde
                            </button>
                            <template x-if="hasResult('{{ $name }}')">
                                <span class="health-status"
                                      x-bind:class="results['{{ $name }}'].status"
                                      x-text="healthLabel(results['{{ $name }}'])"></span>
                            </template>
                            <span class="health-msg"
                                  x-show="hasResult('{{ $name }}')"
                                  x-text="healthText(results['{{ $name }}'])"></span>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="panel settings-panel">
                <h2>Prioridade</h2>
                <p class="panel-note">O primeiro da lista é o provedor principal; os demais atuam como fallback quando ele falha.</p>

                <div class="field" style="margin-bottom:12px">
                    <input type="search" x-model="query" placeholder="Filtrar provedores...">
                </div>

                <div class="priority-list">
                    <template x-for="(provider, index) in order" :key="provider">
                        <div class="priority-row" x-show="filtered(provider)">
                            <span class="priority-grip">☰</span>
                            <input type="hidden" name="priority[]" :value="provider">
                            <span class="priority-name">
                                <span x-text="({!! Js::from($labels) !!})[provider] ?? provider"></span>
                                <small x-text="index === 0 ? 'Provedor principal' : 'Fallback #' + index"></small>
                            </span>
                            <div class="priority-actions">
                                <button type="button" title="Subir" @click="move(index, -1)" :disabled="index === 0">↑</button>
                                <button type="button" title="Descer" @click="move(index, 1)" :disabled="index === order.length - 1">↓</button>
                            </div>
                        </div>
                    </template>
                    <p class="settings-empty" x-show="order.length === 0">Nenhum provedor ativo. Ative ao menos um para salvar.</p>
                </div>
            </section>
        </div>

        <button class="btn btn-primary settings-save" type="submit">Salvar configurações</button>
    </form>
</x-yomi::layout>