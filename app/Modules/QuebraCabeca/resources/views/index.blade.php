<x-QuebraCabeca::layout>
    <div class="h-full flex flex-col max-w-xl mx-auto w-full"
         style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);"
         x-data="quebraCabeca(@js($padrao), @js($enviadas), @js($grupos), @js($errors->any() ? 'biblioteca' : session('tela', 'inicio')))"
         x-init="init()" @resize.window="medir()">

        {{-- Aviso (sucesso / erro de formulário) --}}
        @if (session('success') || $errors->any())
            <div x-data="{ ok: true }" x-show="ok" x-init="setTimeout(() => ok = false, 4000)" @click="ok = false"
                 class="fixed top-3 inset-x-3 z-50 max-w-xl mx-auto rounded-xl px-4 py-3 text-sm font-bold shadow-lg {{ $errors->any() ? 'bg-red-500' : 'bg-emerald-500 text-emerald-950' }}">
                {{ $errors->any() ? $errors->first() : session('success') }}
            </div>
        @endif

        {{-- ============ INÍCIO ============ --}}
        <section x-show="tela === 'inicio'" x-cloak class="flex-1 flex flex-col items-center justify-center px-6 text-center">
            <div class="text-8xl mb-2 pop">🧩</div>
            <h1 class="text-4xl font-black tracking-tight mb-1">Quebra-Cabeça</h1>
            <p class="text-purple-200 mb-10">Reordene as peças e monte a imagem!</p>

            <div class="w-full max-w-xs space-y-3">
                <button type="button" class="btn btn-primary text-xl" @click="tela = 'escolher'">▶ JOGAR</button>
                <button type="button" class="btn btn-sec" @click="tela = 'biblioteca'">🖼️ Minhas imagens</button>
                <a href="{{ url('/') }}" class="block text-sm text-purple-300 pt-4">← Voltar ao portal</a>
            </div>
        </section>

        {{-- ============ ESCOLHER IMAGEM ============ --}}
        <section x-show="tela === 'escolher'" x-cloak class="flex-1 flex flex-col min-h-0">
            <header class="flex items-center gap-3 px-4 py-3">
                <button type="button" class="text-2xl w-10 h-10 rounded-full bg-white/10" @click="tela = 'inicio'">‹</button>
                <h2 class="text-xl font-black">Escolha a imagem</h2>
            </header>

            <div class="px-4 space-y-2 pb-3">
                <div class="flex gap-2">
                    <template x-for="o in [['todas', 'Todas'], ['paisagem', 'Paisagem'], ['retrato', 'Retrato']]" :key="o[0]">
                        <button type="button" class="chip flex-1" :class="filtroOrientacao === o[0] && 'on'"
                                @click="filtroOrientacao = o[0]" x-text="o[1]"></button>
                    </template>
                </div>
                <div class="flex gap-2 scroll-x">
                    <button type="button" class="chip" :class="filtroGrupo === 'todos' && 'on'" @click="filtroGrupo = 'todos'">Todos</button>
                    <button type="button" class="chip" :class="filtroGrupo === 'padrao' && 'on'" @click="filtroGrupo = 'padrao'">Padrão</button>
                    <button type="button" class="chip" :class="filtroGrupo === 'sem' && 'on'" @click="filtroGrupo = 'sem'">Sem grupo</button>
                    <template x-for="g in grupos" :key="g.id">
                        <button type="button" class="chip" :class="filtroGrupo === g.id && 'on'" @click="filtroGrupo = g.id" x-text="g.nome"></button>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-4 pb-6">
                <p x-show="visiveis.length === 0" class="text-center text-purple-300 py-10">
                    Nenhuma imagem neste filtro.<br>
                    <button type="button" class="underline mt-2" @click="tela = 'biblioteca'">Enviar uma imagem</button>
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <template x-for="img in visiveis" :key="img.chave">
                        <button type="button" class="card overflow-hidden text-left active:scale-95 transition-transform"
                                @click="escolher(img)">
                            <div class="w-full aspect-square bg-black/20">
                                <img :src="img.url" :alt="img.nome" class="w-full h-full object-cover" draggable="false">
                            </div>
                            <div class="px-3 py-2 flex items-center justify-between gap-2">
                                <span class="text-sm font-bold truncate" x-text="img.nome"></span>
                                <span class="text-[10px] uppercase text-purple-300" x-text="img.orientacao"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </section>

        {{-- ============ DIFICULDADE ============ --}}
        <section x-show="tela === 'dificuldade'" x-cloak class="flex-1 flex flex-col min-h-0">
            <header class="flex items-center gap-3 px-4 py-3">
                <button type="button" class="text-2xl w-10 h-10 rounded-full bg-white/10" @click="tela = 'escolher'">‹</button>
                <h2 class="text-xl font-black">Dificuldade</h2>
            </header>

            <div class="flex-1 overflow-y-auto px-4 pb-6 space-y-4">
                <div class="flex justify-center">
                    <img :src="imagemUrl" alt="" draggable="false"
                         class="rounded-2xl shadow-xl object-cover border-4 border-white/20"
                         :class="atual && atual.orientacao === 'retrato' ? 'h-56 aspect-[3/4]' : 'w-full max-h-56 aspect-[4/3]'">
                </div>

                <div class="space-y-3">
                    <template x-for="nivel in niveis" :key="nivel.nome">
                        <button type="button" class="card w-full flex items-center gap-4 px-4 py-3 text-left active:scale-95 transition-transform"
                                :class="colunas === nivel.colunas && linhas === nivel.linhas && 'ring-4 ring-amber-400'"
                                @click="colunas = nivel.colunas; linhas = nivel.linhas">
                            <span class="text-3xl" x-text="nivel.emoji"></span>
                            <span class="flex-1">
                                <span class="block font-black" x-text="nivel.nome"></span>
                                <span class="block text-xs text-purple-300" x-text="`${nivel.colunas}×${nivel.linhas} · ${nivel.colunas * nivel.linhas} peças`"></span>
                            </span>
                            <span class="text-xs text-amber-300 font-bold" x-show="recordeDe(nivel) !== null"
                                  x-text="'🏆 ' + formatarTempo(recordeDe(nivel))"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="px-4 pb-4 pt-2">
                <button type="button" class="btn btn-primary text-xl" @click="iniciar()">▶ COMEÇAR</button>
            </div>
        </section>

        {{-- ============ JOGO ============ --}}
        <section x-show="tela === 'jogo'" x-cloak class="flex-1 flex flex-col min-h-0">
            <header class="flex items-center gap-2 px-3 py-3">
                <button type="button" class="text-xl w-10 h-10 rounded-full bg-white/10" @click="sair()">✕</button>
                <div class="flex-1 flex justify-center gap-3 text-sm font-black">
                    <span class="card px-3 py-1.5">⏱️ <span x-text="formatarTempo(segundos)"></span></span>
                    <span class="card px-3 py-1.5">🔁 <span x-text="movimentos"></span></span>
                </div>
                <button type="button" class="text-xl w-10 h-10 rounded-full bg-white/10" @click="iniciar()" title="Reembaralhar">🔀</button>
            </header>

            <div class="flex-1 flex items-center justify-center min-h-0 px-2" x-ref="area">
                <div class="relative select-none" :style="`width: ${colunas * tileW + 8}px; height: ${linhas * tileH + 8}px`">
                    <div class="absolute inset-0 grid p-1 rounded-2xl shadow-2xl"
                         :style="`background: rgba(0,0,0,.45); grid-template-columns: repeat(${colunas}, ${tileW}px); grid-template-rows: repeat(${linhas}, ${tileH}px)`">
                        <template x-for="(peca, pos) in ordem" :key="pos">
                            <div draggable="true"
                                 @click="clicar(pos)"
                                 @dragstart="arrastando = pos; $event.dataTransfer.effectAllowed = 'move'"
                                 @dragover.prevent
                                 @drop.prevent="soltar(pos)"
                                 @dragend="arrastando = null"
                                 class="peca cursor-pointer"
                                 :class="[arrastando === pos ? 'opacity-40' : '', selecionada === pos ? 'peca-sel' : '', juncoes(pos)]">
                                <img :src="imagemUrl" alt="" draggable="false"
                                     class="absolute max-w-none pointer-events-none select-none"
                                     :style="estiloPeca(peca)">
                                <div x-show="selecionada === pos"
                                     class="absolute inset-0 ring-4 ring-amber-400 ring-inset pointer-events-none z-10" style="border-radius: inherit;"></div>
                            </div>
                        </template>
                    </div>
                    <div x-show="previa" class="absolute inset-0 rounded-2xl bg-cover bg-center pointer-events-none z-20"
                         :style="{ backgroundImage: `url('${imagemUrl}')` }"></div>
                </div>
            </div>

            <footer class="px-4 pt-2 pb-4 space-y-2">
                <p class="text-center text-xs text-purple-300">Toque em duas peças (ou arraste) para trocá-las</p>
                <button type="button" class="btn btn-sec touch-none"
                        @pointerdown="previa = true" @pointerup="previa = false"
                        @pointercancel="previa = false" @pointerleave="previa = false" @contextmenu.prevent>
                    👁️ Segure para ver o modelo
                </button>
            </footer>
        </section>

        {{-- ============ VITÓRIA ============ --}}
        <div x-show="venceu" x-cloak class="fixed inset-0 z-40 bg-black/70 flex items-center justify-center p-5">
            <div class="card bg-purple-900 border-2 border-white/20 w-full max-w-sm p-6 text-center pop">
                <div class="text-5xl mb-1">🎉</div>
                <h2 class="text-3xl font-black mb-2">Concluído!</h2>
                <div class="text-4xl mb-3 tracking-widest">
                    <template x-for="n in 3" :key="n">
                        <span :class="n <= estrelas ? '' : 'opacity-25 grayscale'">⭐</span>
                    </template>
                </div>
                <p class="text-purple-100">Tempo <strong x-text="formatarTempo(segundos)"></strong> · Trocas <strong x-text="movimentos"></strong></p>
                <p x-show="recorde" class="text-amber-300 font-black mt-2">🏆 Novo recorde!</p>
                <div class="space-y-2 mt-5">
                    <button type="button" class="btn btn-primary" @click="iniciar()">🔀 Jogar de novo</button>
                    <button type="button" class="btn btn-sec" @click="venceu = false; tela = 'dificuldade'">Outra dificuldade</button>
                    <button type="button" class="btn btn-sec" @click="venceu = false; tela = 'escolher'">Outra imagem</button>
                </div>
            </div>
        </div>

        {{-- ============ BIBLIOTECA ============ --}}
        <section x-show="tela === 'biblioteca'" x-cloak class="flex-1 flex flex-col min-h-0">
            <header class="flex items-center gap-3 px-4 py-3">
                <button type="button" class="text-2xl w-10 h-10 rounded-full bg-white/10" @click="tela = 'inicio'">‹</button>
                <h2 class="text-xl font-black">Minhas imagens</h2>
            </header>

            <div class="flex-1 overflow-y-auto px-4 pb-8 space-y-6">
                {{-- Enviar --}}
                <form method="POST" action="{{ route('quebra-cabeca.imagens.store') }}" enctype="multipart/form-data" class="card p-4 space-y-3">
                    @csrf
                    <h3 class="font-black">📤 Enviar imagem</h3>
                    <input type="file" name="imagem" accept="image/*" required
                           class="block w-full text-sm text-purple-100 file:mr-3 file:rounded-lg file:border-0 file:bg-white/20 file:px-3 file:py-2 file:text-white file:font-bold">
                    <input type="text" name="nome" maxlength="100" placeholder="Nome (opcional)"
                           class="w-full rounded-lg bg-white/10 border-0 text-white placeholder-purple-300">
                    <select name="grupo_id" x-show="grupos.length > 0" class="w-full rounded-lg bg-white/10 border-0 text-white">
                        <option value="" class="text-black">Sem grupo</option>
                        <template x-for="g in grupos" :key="g.id">
                            <option :value="g.id" class="text-black" x-text="g.nome"></option>
                        </template>
                    </select>
                    <button type="submit" class="btn btn-green">Enviar</button>
                </form>

                {{-- Grupos --}}
                <div class="card p-4 space-y-3">
                    <h3 class="font-black">📁 Grupos</h3>
                    <p x-show="grupos.length === 0" class="text-sm text-purple-300">Nenhum grupo ainda.</p>
                    <ul class="space-y-2">
                        <template x-for="g in grupos" :key="g.id">
                            <li class="flex items-center justify-between bg-white/10 rounded-lg px-3 py-2">
                                <span class="font-bold" x-text="g.nome"></span>
                                <form method="POST" :action="g.excluir" onsubmit="return confirm('Remover o grupo? As imagens são mantidas, sem grupo.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-300 font-bold">Remover</button>
                                </form>
                            </li>
                        </template>
                    </ul>
                    <form method="POST" action="{{ route('quebra-cabeca.grupos.store') }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="nome" maxlength="60" required placeholder="Novo grupo (ex.: Família)"
                               class="flex-1 min-w-0 rounded-lg bg-white/10 border-0 text-white placeholder-purple-300">
                        <button type="submit" class="px-4 rounded-lg bg-white/20 font-black">Criar</button>
                    </form>
                </div>

                {{-- Imagens enviadas --}}
                <div class="space-y-3">
                    <h3 class="font-black px-1">🖼️ Enviadas por você</h3>
                    <p x-show="enviadas.length === 0" class="text-sm text-purple-300 px-1">Você ainda não enviou imagens.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="img in enviadas" :key="img.chave">
                            <div class="card overflow-hidden">
                                <div class="w-full aspect-square bg-black/20">
                                    <img :src="img.url" :alt="img.nome" class="w-full h-full object-cover" draggable="false">
                                </div>
                                <div class="p-2 space-y-2">
                                    <p class="text-sm font-bold truncate" x-text="img.nome"></p>
                                    <form method="POST" :action="img.mover" x-show="grupos.length > 0">
                                        @csrf
                                        @method('PATCH')
                                        <select name="grupo_id" onchange="this.form.submit()"
                                                class="w-full rounded-lg bg-white/10 border-0 text-xs text-white py-1.5">
                                            <option value="" class="text-black">Sem grupo</option>
                                            <template x-for="g in grupos" :key="g.id">
                                                <option :value="g.id" class="text-black" :selected="img.grupo_id === g.id" x-text="g.nome"></option>
                                            </template>
                                        </select>
                                    </form>
                                    <form method="POST" :action="img.excluir" onsubmit="return confirm('Remover esta imagem?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full text-xs text-red-300 font-bold py-1">Remover</button>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        function quebraCabeca(padrao, enviadas, grupos, telaInicial) {
            return {
                tela: telaInicial,
                grupos,
                enviadas,
                imagens: [...enviadas, ...padrao],
                atual: null,
                imagemUrl: null,
                filtroGrupo: 'todos',
                filtroOrientacao: 'todas',
                proporcao: 4 / 3,
                tileW: 60,
                tileH: 45,
                colunas: 4,
                linhas: 3,
                ordem: [],
                venceu: false,
                recorde: false,
                estrelas: 0,
                previa: false,
                selecionada: null,
                arrastando: null,
                movimentos: 0,
                segundos: 0,
                timer: null,

                init() {
                    this.$watch('tela', () => this.$nextTick(() => this.medir()));
                },

                get visiveis() {
                    return this.imagens.filter((img) => {
                        const orientacaoOk = this.filtroOrientacao === 'todas' || img.orientacao === this.filtroOrientacao;
                        let grupoOk = true;
                        if (this.filtroGrupo === 'padrao') grupoOk = img.padrao;
                        else if (this.filtroGrupo === 'sem') grupoOk = !img.padrao && img.grupo_id === null;
                        else if (this.filtroGrupo !== 'todos') grupoOk = img.grupo_id === this.filtroGrupo;
                        return orientacaoOk && grupoOk;
                    });
                },

                get niveis() {
                    const retrato = this.atual?.orientacao === 'retrato';
                    const base = [
                        ['Fácil', '🌱', 3, 2],
                        ['Médio', '🔥', 4, 3],
                        ['Difícil', '⚡', 6, 4],
                        ['Expert', '💀', 8, 6],
                    ];
                    return base.map(([nome, emoji, c, l]) => ({
                        nome,
                        emoji,
                        colunas: retrato ? l : c,
                        linhas: retrato ? c : l,
                    }));
                },

                escolher(img) {
                    this.atual = img;
                    this.imagemUrl = img.url;
                    const medio = this.niveis[1];
                    this.colunas = medio.colunas;
                    this.linhas = medio.linhas;
                    const probe = new Image();
                    probe.onload = () => {
                        this.proporcao = probe.naturalWidth / probe.naturalHeight;
                        this.medir();
                    };
                    probe.src = img.url;
                    this.tela = 'dificuldade';
                },

                // Peças com tamanho em pixels inteiros: evita linhas finas entre peças unidas (subpixel).
                medir() {
                    const area = this.$refs.area;
                    if (!area || this.tela !== 'jogo') return;
                    const largura = area.clientWidth - 16;
                    const altura = area.clientHeight - 8;
                    const calcularAltura = (w) => Math.max(1, Math.round((w * this.colunas) / (this.linhas * this.proporcao)));

                    let w = Math.max(10, Math.floor((Math.min(largura, altura * this.proporcao) - 8) / this.colunas));
                    while (w > 10 && (this.linhas * calcularAltura(w) + 8 > altura || this.colunas * w + 8 > largura)) w--;

                    this.tileW = w;
                    this.tileH = calcularAltura(w);
                },

                chaveRecorde(c, l) {
                    return `quebra-cabeca:${this.atual?.chave}:${c}x${l}`;
                },

                recordeDe(nivel) {
                    try {
                        const v = localStorage.getItem(this.chaveRecorde(nivel.colunas, nivel.linhas));
                        return v === null ? null : Number(v);
                    } catch (e) {
                        return null;
                    }
                },

                iniciar() {
                    const total = this.colunas * this.linhas;
                    let ordem;
                    do {
                        ordem = Array.from({ length: total }, (_, i) => i);
                        for (let i = total - 1; i > 0; i--) {
                            const j = Math.floor(Math.random() * (i + 1));
                            [ordem[i], ordem[j]] = [ordem[j], ordem[i]];
                        }
                    } while (ordem.every((p, i) => p === i));

                    this.ordem = ordem;
                    this.movimentos = 0;
                    this.segundos = 0;
                    this.selecionada = null;
                    this.venceu = false;
                    this.recorde = false;
                    this.previa = false;
                    this.tela = 'jogo';
                    clearInterval(this.timer);
                    this.timer = setInterval(() => this.segundos++, 1000);
                    this.$nextTick(() => this.medir());
                },

                sair() {
                    clearInterval(this.timer);
                    this.venceu = false;
                    this.tela = 'dificuldade';
                },

                // Lados da peça encostados numa vizinha que está na posição correta em relação a ela.
                juncoes(pos) {
                    const cols = this.colunas;
                    const peca = this.ordem[pos];
                    const col = pos % cols;
                    const lin = Math.floor(pos / cols);
                    const classes = [];
                    if (lin > 0 && this.ordem[pos - cols] === peca - cols) classes.push('j-t');
                    if (lin < this.linhas - 1 && this.ordem[pos + cols] === peca + cols) classes.push('j-b');
                    if (col > 0 && peca % cols !== 0 && this.ordem[pos - 1] === peca - 1) classes.push('j-l');
                    if (col < cols - 1 && peca % cols !== cols - 1 && this.ordem[pos + 1] === peca + 1) classes.push('j-r');
                    if (classes.length > 0) classes.push('j-any');
                    return classes.join(' ');
                },

                estiloPeca(peca) {
                    const col = peca % this.colunas;
                    const lin = Math.floor(peca / this.colunas);
                    return `width: ${this.colunas * 100}%; height: ${this.linhas * 100}%; left: -${col * 100}%; top: -${lin * 100}%;`;
                },

                clicar(pos) {
                    if (this.venceu) return;
                    if (this.selecionada === null) {
                        this.selecionada = pos;
                    } else if (this.selecionada === pos) {
                        this.selecionada = null;
                    } else {
                        this.trocar(this.selecionada, pos);
                        this.selecionada = null;
                    }
                },

                soltar(destino) {
                    if (this.arrastando !== null && this.arrastando !== destino && !this.venceu) {
                        this.trocar(this.arrastando, destino);
                    }
                    this.arrastando = null;
                    this.selecionada = null;
                },

                trocar(a, b) {
                    const nova = [...this.ordem];
                    [nova[a], nova[b]] = [nova[b], nova[a]];
                    this.ordem = nova;
                    this.movimentos++;
                    if (nova.every((p, i) => p === i)) this.concluir();
                },

                concluir() {
                    clearInterval(this.timer);
                    const total = this.colunas * this.linhas;
                    this.estrelas = this.movimentos <= total * 1.5 ? 3 : (this.movimentos <= total * 3 ? 2 : 1);

                    const chave = this.chaveRecorde(this.colunas, this.linhas);
                    try {
                        const anterior = localStorage.getItem(chave);
                        if (anterior === null || this.segundos < Number(anterior)) {
                            this.recorde = true;
                            localStorage.setItem(chave, String(this.segundos));
                        }
                    } catch (e) {
                        // sem armazenamento local: segue sem recorde
                    }
                    this.venceu = true;
                },

                formatarTempo(s) {
                    return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
                },
            };
        }
    </script>
</x-QuebraCabeca::layout>
