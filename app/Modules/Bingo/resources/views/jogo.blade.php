<x-Bingo::layout>
    @php
        $partidaData = json_encode([
            'codigo' => $partida->codigo,
            'tema' => $partida->tema,
            'status' => $partida->status,
            'modo_gestor' => $partida->modo_gestor,
            'numeros_sorteados' => $partida->numeros_sorteados ?? [],
        ]);
    @endphp

    <div class="min-h-screen flex flex-col"
         x-data="bingoGame({{ $partidaData }}, '{{ $temaUrl }}', '{{ $joinUrl }}')"
         x-init="init()"
         x-cloak>

        {{-- LOADING --}}
        <div x-show="loading" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-6xl mb-4 animate-bounce">🎲</div>
                <p class="text-xl font-bold text-amber-700">Carregando...</p>
            </div>
        </div>

        <template x-if="!loading">
            <div class="flex-1 flex flex-col">
                {{-- HEADER --}}
                <header class="bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-white px-4 py-3 shadow-lg">
                    <div class="max-w-4xl mx-auto flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🎉</span>
                            <span class="font-bold text-lg">BINGO!</span>
                            <span class="bg-white/20 text-xs px-2 py-0.5 rounded-full font-bold" x-text="'#' + partida.codigo"></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="bg-white/20 px-3 py-1 rounded-full" x-text="jogadores.length + ' 👥'"></span>
                        </div>
                    </div>
                </header>

                {{-- JOIN FORM --}}
                <template x-if="!token && !donoToken && partida.status === 'espera'">
                    <div class="flex-1 flex items-center justify-center p-6">
                        <div class="w-full max-w-sm bg-white/90 rounded-3xl p-8 shadow-xl border-2 border-amber-200 text-center">
                            <div class="text-6xl mb-4">🎮</div>
                            <h2 class="text-2xl font-black text-amber-800 mb-2">Entrar no Bingo!</h2>
                            <p class="text-amber-600/70 text-sm mb-6">Digite seu nome para entrar na partida</p>
                            <form x-on:submit.prevent="join()" class="space-y-4">
                                <input type="text" x-model="joinNome"
                                       placeholder="Seu nome"
                                       maxlength="50"
                                       class="w-full text-center text-lg px-4 py-3 rounded-2xl border-2 border-amber-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-300 outline-none font-bold">
                                <p x-show="joinError" x-text="joinError" class="text-rose-500 text-xs"></p>
                                <button type="submit"
                                        :disabled="!joinNome || joinLoading"
                                        :class="{'opacity-50 cursor-not-allowed': !joinNome || joinLoading}"
                                        class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold py-4 px-6 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all text-lg">
                                    <span x-show="!joinLoading">🚀 Entrar!</span>
                                    <span x-show="joinLoading">⏳ Entrando...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </template>

                {{-- LOBBY (waiting for host to start) --}}
                <template x-if="token && partida.status === 'espera'">
                    <div class="flex-1 flex flex-col lg:flex-row gap-6 p-4 max-w-6xl mx-auto w-full">
                        {{-- Host: QRCode + Controls --}}
                        <div x-show="eDono" class="lg:w-1/3 bg-white/90 rounded-3xl p-6 shadow-lg border-2 border-amber-200">
                            <h3 class="text-lg font-black text-amber-800 mb-4 text-center">📱 Conecte-se</h3>
                            <div id="qrcode" class="flex justify-center mb-4"></div>
                            <p class="text-center text-xs text-amber-600/70 mb-4">
                                Escaneie o QRCode para entrar nesta partida
                            </p>
                            <p class="text-center text-sm font-bold text-amber-700 mb-4 bg-amber-50 rounded-xl py-2 px-3">
                                ou acesse: <span class="text-emerald-600 underline" x-text="joinUrl"></span>
                            </p>
                            <div class="bg-amber-50 rounded-2xl p-4 mb-4">
                                <p class="text-xs font-bold text-amber-700 mb-2">🎮 Jogadores conectados:</p>
                                <template x-if="jogadores.length === 0">
                                    <p class="text-xs text-amber-500 italic">Aguardando jogadores...</p>
                                </template>
                                <div class="space-y-1">
                                    <template x-for="j in jogadores" :key="j.id">
                                        <div class="flex items-center gap-2 text-sm text-amber-800">
                                            <span>👤</span>
                                            <span x-text="j.nome"></span>
                                            <span x-show="j.bingo_feito" class="text-xs bg-amber-200 px-2 rounded-full">🏆</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <button x-on:click="iniciar()"
                                    class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all text-lg">
                                🚀 Iniciar Partida!
                            </button>
                        </div>

                        {{-- Player card preview --}}
                        <div class="flex-1 flex flex-col items-center justify-center">
                            <template x-if="meuJogador && meuJogador.cartela">
                                <div class="w-full max-w-md">
                                    <div class="bg-white/90 rounded-3xl p-4 shadow-lg border-2 border-amber-200">
                                        <div class="text-center mb-3">
                                            <h3 class="font-black text-amber-800 text-lg" x-text="meuJogador.nome + ' 🎯'"></h3>
                                            <p class="text-xs text-amber-600/70">Sua cartela foi gerada!</p>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 mb-3">
                                            <template x-for="(row, r) in meuJogador.cartela.numeros" :key="'r'+r">
                                                <template x-for="(num, c) in row" :key="'c'+r+'-'+c">
                                                    <div class="aspect-square min-h-[70px] rounded-xl border-2 border-amber-200 bg-white overflow-hidden relative"
                                                         :style="getSpriteStyle(num)">
                                                        <span class="absolute bottom-0.5 right-0.5 bg-amber-500 text-white text-[9px] sm:text-[11px] font-black px-1.5 rounded"
                                                              x-text="String(num).padStart(2,'0')"></span>
                                                    </div>
                                                </template>
                                            </template>
                                        </div>
                                        <button x-on:click="trocarCartela()"
                                                class="w-full bg-gradient-to-r from-amber-400 to-orange-400 text-white font-bold py-3 rounded-2xl shadow hover:shadow-lg hover:scale-[1.02] transition-all text-sm">
                                            🔄 Trocar Cartela
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="eDono && !meuJogador">
                                <div class="text-center bg-white/90 rounded-3xl p-8 shadow-lg border-2 border-amber-200">
                                    <div class="text-5xl mb-3">🎮</div>
                                    <h3 class="text-xl font-black text-amber-800">Modo Gestor</h3>
                                    <p class="text-sm text-amber-600/70">Você está gerenciando os sorteios</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- GAME (match started) --}}
                <template x-if="partida.status === 'jogando' || partida.status === 'finalizada'">
                    <div class="flex-1 flex flex-col lg:flex-row gap-6 p-4 max-w-6xl mx-auto w-full">
                        {{-- Host draw controls --}}
                        <div x-show="eDono" class="lg:w-1/3 bg-white/90 rounded-3xl p-6 shadow-lg border-2 border-amber-200 self-start">
                            <h3 class="text-lg font-black text-amber-800 mb-4 text-center">🎰 Sorteio</h3>
                            <div class="bg-gradient-to-b from-amber-50 to-amber-100 rounded-2xl p-6 text-center mb-4">
                                <div class="w-24 h-24 mx-auto rounded-full bg-white shadow-lg border-4 border-amber-300 flex items-center justify-center mb-3">
                                    <span class="text-3xl font-black text-amber-700"
                                          x-text="ultimoSorteado ? String(ultimoSorteado).padStart(2,'0') : '?'"></span>
                                </div>
                                <div x-show="ultimoSorteado" class="w-20 h-20 mx-auto rounded-xl overflow-hidden border-2 border-amber-300 shadow-inner mb-3"
                                     :style="getSpriteStyle(ultimoSorteado)"></div>
                                <p class="text-xs text-amber-600/70 font-medium" x-text="'Sorteados: ' + partida.numeros_sorteados.length + '/25'"></p>
                            </div>
                            <button x-on:click="sortear()"
                                    :disabled="partida.numeros_sorteados.length >= 25 || partida.status === 'finalizada'"
                                    :class="{'opacity-50 cursor-not-allowed': partida.numeros_sorteados.length >= 25 || partida.status === 'finalizada'}"
                                    class="w-full bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all text-lg mb-4">
                                🎲 Sortear!
                            </button>
                            <div>
                                <h4 class="text-xs font-bold text-amber-700 mb-2">📋 Sorteados:</h4>
                                <div class="grid grid-cols-5 gap-1">
                                    <template x-for="n in 25" :key="n">
                                        <div class="aspect-square rounded-lg flex items-center justify-center text-[10px] font-bold border"
                                             :class="partida.numeros_sorteados.includes(n) ? 'bg-amber-400 text-white border-amber-500' : 'bg-amber-50 text-amber-300 border-amber-200'"
                                             x-text="String(n).padStart(2,'0')">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Player card --}}
                        <div x-show="meuJogador && meuJogador.cartela" class="flex-1 flex flex-col items-center w-full max-w-lg mx-auto px-2 sm:px-0">
                            {{-- Drawn item display (above card) --}}
                            <div x-show="ultimoSorteado" class="w-full bg-white/90 rounded-2xl p-3 sm:p-4 shadow-lg border-2 border-amber-200 mb-3 text-center bounce-in">
                                <p class="text-[10px] sm:text-xs font-bold text-amber-600/70 uppercase tracking-wider mb-1">🎯 Sorteado</p>
                                <div class="flex items-center justify-center gap-3 sm:gap-4">
                                    <div class="w-12 h-12 sm:w-20 sm:h-20 rounded-xl sm:rounded-2xl overflow-hidden border-3 sm:border-4 border-amber-400 shadow-lg"
                                         :style="getSpriteStyle(ultimoSorteado)"></div>
                                    <span class="text-3xl sm:text-5xl font-black text-amber-600" x-text="String(ultimoSorteado).padStart(2,'0')"></span>
                                </div>
                            </div>

                            {{-- Card --}}
                            <div class="w-full bg-white/90 rounded-2xl sm:rounded-3xl p-3 sm:p-5 shadow-xl border-2 border-amber-200">
                                <div class="text-center mb-2">
                                    <h3 class="font-black text-amber-800 text-base sm:text-lg" x-text="meuJogador.nome"></h3>
                                </div>
                                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                                    <template x-for="(row, r) in meuJogador.cartela.numeros" :key="'r'+r">
                                        <template x-for="(num, c) in row" :key="'c'+r+'-'+c">
                                            <button x-on:click="toggleMarca(r, c)"
                                                    class="cell-btn aspect-square min-h-[60px] sm:min-h-[80px] rounded-xl sm:rounded-2xl border-2 overflow-hidden relative focus:outline-none active:scale-95"
                                                    :class="estaMarcada(r, c) ? 'border-rose-400 bg-rose-50 shadow-md' : 'border-amber-200 bg-white hover:border-amber-400 hover:shadow-md'">
                                                {{-- Sprite image --}}
                                                <div class="absolute inset-0" :style="getSpriteStyle(num)"></div>
                                                {{-- Number badge --}}
                                                <span class="absolute bottom-0.5 right-0.5 bg-amber-500/90 text-white text-[9px] sm:text-[11px] font-black px-1 sm:px-1.5 rounded"
                                                      x-text="String(num).padStart(2,'0')"></span>
                                                {{-- Mark overlay --}}
                                                <div x-show="estaMarcada(r, c)"
                                                     class="absolute inset-0 flex items-center justify-center stamp">
                                                    <div class="w-3/4 h-3/4 rounded-full border-3 border-rose-500 border-dashed flex items-center justify-center bg-rose-500/15 rotate-12">
                                                        <svg class="w-1/2 h-1/2 text-rose-500" fill="currentColor" viewBox="0 0 100 100">
                                                            <path d="M50,75 C45,75 30,60 30,45 C30,30 40,20 50,20 C60,20 70,30 70,45 C70,60 55,75 50,75 Z M50,45 A10,10 0 1,0 50,45.1 Z"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </template>
                                </div>
                                {{-- BINGO button --}}
                                <button x-on:click="declararBingo()"
                                        x-show="partida.status === 'jogando'"
                                        class="w-full mt-3 bg-gradient-to-r from-rose-500 to-pink-500 text-white font-black py-3 sm:py-4 px-6 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all text-base sm:text-lg">
                                    🏆 BINGO!
                                </button>
                            </div>

                            {{-- Player list --}}
                            <div class="w-full max-w-md mt-4 bg-white/80 rounded-2xl p-4 shadow border border-amber-200">
                                <p class="text-xs font-bold text-amber-700 mb-2">👥 Jogadores</p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="j in jogadores" :key="j.id">
                                        <span class="text-sm bg-amber-100 text-amber-800 px-3 py-1 rounded-full font-medium"
                                              :class="{'bg-emerald-100 text-emerald-800': j.bingo_feito}">
                                            <span x-text="j.nome"></span>
                                            <span x-show="j.bingo_feito">🏆</span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- No card (gestor) --}}
                        <div x-show="!meuJogador && !eDono" class="flex-1 flex items-center justify-center">
                            <div class="text-center bg-white/90 rounded-3xl p-8 shadow-lg border-2 border-amber-200">
                                <div class="text-5xl mb-3">⏳</div>
                                <p class="text-lg font-bold text-amber-800">Aguardando sua cartela...</p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- BINGO MODAL --}}
        <div x-show="showBingoModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl border-4 border-amber-400 bounce-in">
                <div class="text-7xl mb-4">🏆</div>
                <h2 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-rose-500 via-amber-500 to-emerald-500 uppercase">BINGO!</h2>
                <p class="text-amber-700 mt-2 font-medium" x-text="'🎉 ' + vencedor + ' venceu!'"></p>
                <button x-on:click="showBingoModal = false"
                        class="mt-6 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold px-8 py-3 rounded-2xl shadow-lg hover:shadow-xl transition-all">
                    🎉 Fechar
                </button>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .border-3 { border-width: 3px; }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bingoGame', (partidaData, temaUrl, joinUrl) => ({
                // State
                token: localStorage.getItem('bingo_token') || '',
                donoToken: localStorage.getItem('bingo_dono_token') || '',
                partida: partidaData,
                temaUrl: temaUrl,
                joinUrl: joinUrl,
                jogadores: [],
                meuJogador: null,
                eDono: false,
                loading: true,

                // UI
                joinNome: '',
                joinError: '',
                joinLoading: false,
                ultimoSorteado: null,
                vencedor: '',
                showBingoModal: false,
                pollInterval: null,

                init() {
                    this.eDono = this.donoToken && this.token === this.donoToken;
                    this.fetchEstado().then(() => {
                        this.loading = false;
                        this.gerarQRCode();
                        this.startPolling();
                    });
                },

                startPolling() {
                    this.pollInterval = setInterval(() => {
                        this.fetchEstado();
                    }, 2000);
                },

                async fetchEstado() {
                    const headers = { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content };
                    if (this.token) headers['X-Bingo-Token'] = this.token;

                    try {
                        const resp = await fetch('{{ route('bingo.estado', ['codigo' => $partida->codigo]) }}', { headers });
                        if (!resp.ok) return;
                        const data = await resp.json();

                        this.partida = data.partida;
                        this.jogadores = data.jogadores;
                        this.temaUrl = data.tema_url;

                        if (data.meu_jogador) {
                            this.meuJogador = data.meu_jogador;
                        }

                        this.eDono = data.e_dono || false;

                        const sorteados = data.partida.numeros_sorteados || [];
                        this.ultimoSorteado = sorteados.length > 0 ? sorteados[sorteados.length - 1] : null;

                        if (data.partida.status === 'finalizada' && data.jogadores.some(j => j.bingo_feito)) {
                            const vencedor = data.jogadores.find(j => j.bingo_feito);
                            if (vencedor && !this.showBingoModal) {
                                this.vencedor = vencedor.nome;
                                this.showBingoModal = true;
                                this.lancarConfete();
                                this.playSom('bingo');
                            }
                        }
                    } catch (e) {
                        console.warn('Poll error:', e);
                    }
                },

                join() {
                    if (!this.joinNome || this.joinLoading) return;
                    this.joinLoading = true;
                    this.joinError = '';

                    fetch('{{ route('bingo.join', ['codigo' => $partida->codigo]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ nome: this.joinNome }),
                    })
                    .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error || 'Erro ao entrar'); }))
                    .then(data => {
                        this.token = data.token;
                        this.meuJogador = { id: data.jogador_id, nome: this.joinNome, cartela: { numeros: data.cartela, marcacoes: [] } };
                        localStorage.setItem('bingo_token', data.token);
                        this.joinLoading = false;
                        this.fetchEstado();
                    })
                    .catch(e => {
                        this.joinError = e.message;
                        this.joinLoading = false;
                    });
                },

                trocarCartela() {
                    if (this.partida.status !== 'espera') return;

                    fetch('{{ route('bingo.trocar-cartela', ['codigo' => $partida->codigo]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Bingo-Token': this.token },
                    })
                    .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error); }))
                    .then(data => {
                        this.meuJogador.cartela.numeros = data.cartela;
                        this.meuJogador.cartela.marcacoes = [];
                        this.playSom('trocar');
                    })
                    .catch(e => alert(e.message));
                },

                iniciar() {
                    fetch('{{ route('bingo.iniciar', ['codigo' => $partida->codigo]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Bingo-Token': this.token },
                    })
                    .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error); }))
                    .then(() => this.fetchEstado())
                    .catch(e => alert(e.message));
                },

                sortear() {
                    if (this.partida.numeros_sorteados.length >= 25 || this.partida.status === 'finalizada') return;

                    fetch('{{ route('bingo.sortear', ['codigo' => $partida->codigo]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Bingo-Token': this.token },
                    })
                    .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error); }))
                    .then(data => {
                        this.ultimoSorteado = data.numero;
                        this.partida.numeros_sorteados = data.numeros_sorteados;
                        this.playSom('sortear');
                    })
                    .catch(e => alert(e.message));
                },

                toggleMarca(linha, coluna) {
                    if (this.partida.status !== 'jogando') return;

                    fetch('{{ route('bingo.marcar', ['codigo' => $partida->codigo]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Bingo-Token': this.token },
                        body: JSON.stringify({ linha, coluna }),
                    })
                    .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error); }))
                    .then(data => {
                        this.meuJogador.cartela.marcacoes = data.marcacoes;
                        this.playSom('marcar');
                    })
                    .catch(e => console.warn(e));
                },

                declararBingo() {
                    if (this.partida.status !== 'jogando') return;

                    fetch('{{ route('bingo.declarar-bingo', ['codigo' => $partida->codigo]) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Bingo-Token': this.token },
                    })
                    .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error); }))
                    .then(data => {
                        this.vencedor = data.vencedor;
                        this.showBingoModal = true;
                        this.partida.status = 'finalizada';
                        this.lancarConfete();
                        this.playSom('bingo');
                    })
                    .catch(e => alert(e.message || 'Sua cartela ainda não completou!'));
                },

                estaMarcada(linha, coluna) {
                    if (!this.meuJogador?.cartela?.marcacoes) return false;
                    return this.meuJogador.cartela.marcacoes.includes(linha + '-' + coluna);
                },

                getSpriteStyle(n) {
                    if (!this.temaUrl || !n) return '';
                    const idx = n - 1;
                    const col = idx % 5;
                    const row = Math.floor(idx / 5);
                    return `background-image: url('${this.temaUrl}'); background-size: 500% 500%; background-position: ${col * 25}% ${row * 25}%; background-repeat: no-repeat;`;
                },

                gerarQRCode() {
                    if (!this.eDono || !this.joinUrl) return;
                    setTimeout(() => {
                        const el = document.getElementById('qrcode');
                        if (el && !el.hasChildNodes()) {
                            new QRCode(el, {
                                text: this.joinUrl,
                                width: 180,
                                height: 180,
                                colorDark: '#d97706',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.H,
                            });
                        }
                    }, 100);
                },

                playSom(tipo) {
                    try {
                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (!AudioCtx) return;
                        const ctx = new AudioCtx();

                        if (tipo === 'marcar' || tipo === 'trocar') {
                            const o = ctx.createOscillator();
                            const g = ctx.createGain();
                            o.type = 'sine';
                            o.frequency.setValueAtTime(600, ctx.currentTime);
                            o.frequency.exponentialRampToValueAtTime(900, ctx.currentTime + 0.08);
                            g.gain.setValueAtTime(0.08, ctx.currentTime);
                            g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
                            o.connect(g); g.connect(ctx.destination);
                            o.start(); o.stop(ctx.currentTime + 0.12);
                        } else if (tipo === 'sortear') {
                            const o = ctx.createOscillator();
                            const g = ctx.createGain();
                            o.type = 'triangle';
                            o.frequency.setValueAtTime(400, ctx.currentTime);
                            o.frequency.exponentialRampToValueAtTime(800, ctx.currentTime + 0.15);
                            g.gain.setValueAtTime(0.1, ctx.currentTime);
                            g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                            o.connect(g); g.connect(ctx.destination);
                            o.start(); o.stop(ctx.currentTime + 0.2);
                        } else if (tipo === 'bingo') {
                            [523.25, 659.25, 783.99, 1046.5].forEach((f, i) => {
                                const o = ctx.createOscillator();
                                const g = ctx.createGain();
                                o.type = 'triangle';
                                o.frequency.setValueAtTime(f, ctx.currentTime + i * 0.1);
                                g.gain.setValueAtTime(0.08, ctx.currentTime + i * 0.1);
                                g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + i * 0.1 + 0.4);
                                o.connect(g); g.connect(ctx.destination);
                                o.start(ctx.currentTime + i * 0.1);
                                o.stop(ctx.currentTime + i * 0.1 + 0.4);
                            });
                        }
                        setTimeout(() => ctx.close(), 500);
                    } catch(e) { console.warn('Audio error'); }
                },

                lancarConfete() {
                    const colors = ['#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316'];
                    for (let i = 0; i < 80; i++) {
                        const el = document.createElement('div');
                        el.className = 'confetti-piece';
                        el.style.left = Math.random() * 100 + '%';
                        el.style.background = colors[Math.floor(Math.random() * colors.length)];
                        el.style.width = (Math.random() * 8 + 4) + 'px';
                        el.style.height = (Math.random() * 8 + 4) + 'px';
                        el.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                        el.style.animationDuration = (Math.random() * 2 + 2) + 's';
                        el.style.animationDelay = (Math.random() * 1.5) + 's';
                        document.body.appendChild(el);
                        setTimeout(() => el.remove(), 4000);
                    }
                },
            }));
        });
    </script>
    @endpush
</x-Bingo::layout>
