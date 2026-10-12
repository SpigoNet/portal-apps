<x-QuebraCabeca::layout>
    <div class="max-w-5xl mx-auto p-4 sm:p-6" x-data="quebraCabeca(@js($imagens), @js($enviadas))" x-init="init()">
        <header class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-2xl font-bold">🧩 Quebra-Cabeça</h1>
            <a href="{{ url('/') }}" class="text-sm text-slate-400 hover:text-white">← Portal</a>
        </header>

        {{-- Configuração --}}
        <section class="bg-slate-800 rounded-xl p-4 mb-4 space-y-4" x-show="!jogando">
            <div>
                <p class="text-sm text-slate-400 mb-2">Escolha uma imagem</p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <template x-for="(img, i) in padrao" :key="i">
                        <button type="button" @click="selecionar(i)"
                                class="rounded-lg overflow-hidden border-2 aspect-[4/3]"
                                :class="indice === i ? 'border-amber-400' : 'border-transparent'">
                            <img :src="img.url" :alt="img.nome" class="w-full h-full object-cover">
                        </button>
                    </template>
                    <template x-for="(img, i) in enviadas" :key="img.id">
                        <div class="relative">
                            <button type="button" @click="selecionar(padrao.length + i)"
                                    class="w-full rounded-lg overflow-hidden border-2 aspect-[4/3]"
                                    :class="indice === padrao.length + i ? 'border-amber-400' : 'border-transparent'">
                                <img :src="img.url" :alt="img.nome" class="w-full h-full object-cover">
                            </button>
                            <form method="POST" :action="img.excluir" onsubmit="return confirm('Remover esta imagem?')"
                                  class="absolute top-1 right-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-black/60 hover:bg-red-600 rounded px-1.5 text-xs" title="Remover">✕</button>
                            </form>
                        </div>
                    </template>
                </div>
            </div>

            <form method="POST" action="{{ route('quebra-cabeca.imagens.store') }}" enctype="multipart/form-data"
                  class="flex flex-wrap items-end gap-3 border-t border-slate-700 pt-4">
                @csrf
                <label class="text-sm text-slate-400">Enviar sua imagem
                    <input type="file" name="imagem" accept="image/*" required
                           class="mt-1 block text-sm text-slate-300 file:mr-3 file:rounded file:border-0 file:bg-slate-700 file:px-3 file:py-2 file:text-white">
                </label>
                <label class="text-sm text-slate-400">Nome (opcional)
                    <input type="text" name="nome" maxlength="100" class="mt-1 block rounded bg-slate-700 border-0 text-white">
                </label>
                <button type="submit" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg">Enviar</button>
            </form>
            @if (session('success'))
                <p class="text-sm text-emerald-400">{{ session('success') }}</p>
            @endif
            @error('imagem')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-2 gap-4 max-w-sm">
                <label class="text-sm text-slate-400">Colunas
                    <input type="number" min="2" max="10" x-model.number="colunas"
                           class="mt-1 w-full rounded bg-slate-700 border-0 text-white">
                </label>
                <label class="text-sm text-slate-400">Linhas
                    <input type="number" min="2" max="10" x-model.number="linhas"
                           class="mt-1 w-full rounded bg-slate-700 border-0 text-white">
                </label>
            </div>

            <button type="button" @click="iniciar()" :disabled="!imagemUrl"
                    class="bg-amber-500 hover:bg-amber-400 disabled:opacity-40 text-slate-900 font-bold px-6 py-3 rounded-lg">
                Embaralhar e jogar
            </button>
        </section>

        {{-- Jogo --}}
        <section x-show="jogando" x-cloak>
            <div class="flex flex-wrap items-center gap-4 mb-3 text-sm">
                <span>⏱️ <strong x-text="formatarTempo(segundos)"></strong></span>
                <span>🔁 <strong x-text="movimentos"></strong> trocas</span>
                <span x-show="melhor !== null" class="text-amber-300">🏆 Melhor: <span x-text="formatarTempo(melhor)"></span></span>
                <div class="ml-auto flex gap-2">
                    <button type="button" @pointerdown="previa = true" @pointerup="previa = false"
                            @pointercancel="previa = false" @pointerleave="previa = false"
                            @contextmenu.prevent
                            class="px-3 py-1.5 rounded bg-slate-700 hover:bg-slate-600 touch-none select-none">👁️ Ver modelo</button>
                    <button type="button" @click="iniciar()" class="px-3 py-1.5 rounded bg-slate-700 hover:bg-slate-600">Reembaralhar</button>
                    <button type="button" @click="sair()" class="px-3 py-1.5 rounded bg-slate-700 hover:bg-slate-600">Trocar imagem</button>
                </div>
            </div>

            <div class="relative mx-auto select-none" :style="`width: min(100%, ${larguraMax}px); aspect-ratio: ${proporcao}`">
                <div class="absolute inset-0 grid gap-px bg-slate-950 rounded-lg overflow-hidden"
                     :style="`grid-template-columns: repeat(${colunas}, 1fr); grid-template-rows: repeat(${linhas}, 1fr)`">
                    <template x-for="(peca, pos) in ordem" :key="pos">
                        <div draggable="true"
                             @click="clicar(pos)"
                             @dragstart="arrastando = pos; $event.dataTransfer.effectAllowed = 'move'"
                             @dragover.prevent
                             @drop.prevent="soltar(pos)"
                             @dragend="arrastando = null"
                             class="cursor-pointer"
                             :class="[
                                 selecionada === pos ? 'ring-4 ring-amber-400 ring-inset' : '',
                                 arrastando === pos ? 'opacity-40' : '',
                                 venceu ? '' : 'hover:brightness-110'
                             ]"
                             :style="estiloPeca(peca)"></div>
                    </template>
                </div>
                <div x-show="previa" class="absolute inset-0 rounded-lg bg-cover bg-center pointer-events-none z-20"
                     :style="`background-image: url('${imagemUrl}')`"></div>
            </div>
            <p class="text-center text-xs text-slate-500 mt-3">Arraste uma peça sobre outra, ou toque em duas peças, para trocá-las de lugar.</p>
        </section>

        {{-- Vitória --}}
        <div x-show="venceu" x-cloak class="fixed inset-0 bg-black/70 flex items-center justify-center p-4 z-50">
            <div class="bg-slate-800 rounded-2xl p-8 text-center max-w-sm">
                <div class="text-5xl mb-2">🎉</div>
                <h2 class="text-2xl font-bold mb-2">Concluído!</h2>
                <p class="text-slate-300 mb-1">Tempo: <strong x-text="formatarTempo(segundos)"></strong></p>
                <p class="text-slate-300 mb-1">Trocas: <strong x-text="movimentos"></strong></p>
                <p x-show="recorde" class="text-amber-300 font-semibold mb-1">🏆 Novo recorde!</p>
                <div class="flex gap-2 justify-center mt-5">
                    <button type="button" @click="iniciar()" class="bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold px-4 py-2 rounded-lg">Jogar de novo</button>
                    <button type="button" @click="sair()" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg">Outra imagem</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function quebraCabeca(padrao, enviadas) {
            return {
                padrao,
                imagens: [...padrao, ...enviadas],
                enviadas,
                indice: 0,
                imagemUrl: padrao[0]?.url ?? null,
                proporcao: 4 / 3,
                larguraMax: 900,
                colunas: 4,
                linhas: 3,
                ordem: [],
                jogando: false,
                venceu: false,
                recorde: false,
                previa: false,
                selecionada: null,
                arrastando: null,
                movimentos: 0,
                segundos: 0,
                melhor: null,
                timer: null,

                init() {
                    this.medirImagem();
                },

                selecionar(i) {
                    this.indice = i;
                    this.imagemUrl = this.imagens[i].url;
                    this.medirImagem();
                },

                medirImagem() {
                    const img = new Image();
                    img.onload = () => {
                        this.proporcao = img.naturalWidth / img.naturalHeight;
                        this.larguraMax = Math.min(900, Math.round(window.innerHeight * 0.7 * this.proporcao));
                    };
                    img.src = this.imagemUrl;
                },

                chaveRecorde() {
                    const img = this.imagens[this.indice];
                    const id = img?.id ? 'u' + img.id : this.indice;
                    return `quebra-cabeca:${id}:${this.colunas}x${this.linhas}`;
                },

                lerRecorde() {
                    try {
                        const v = localStorage.getItem(this.chaveRecorde());
                        return v === null ? null : Number(v);
                    } catch (e) {
                        return null;
                    }
                },

                iniciar() {
                    this.colunas = Math.min(10, Math.max(2, this.colunas || 2));
                    this.linhas = Math.min(10, Math.max(2, this.linhas || 2));
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
                    this.melhor = this.lerRecorde();
                    this.previa = false;
                    this.jogando = true;
                    clearInterval(this.timer);
                    this.timer = setInterval(() => this.segundos++, 1000);
                },

                sair() {
                    clearInterval(this.timer);
                    this.jogando = false;
                    this.venceu = false;
                },

                estiloPeca(peca) {
                    const col = peca % this.colunas;
                    const lin = Math.floor(peca / this.colunas);
                    const x = this.colunas > 1 ? (col / (this.colunas - 1)) * 100 : 0;
                    const y = this.linhas > 1 ? (lin / (this.linhas - 1)) * 100 : 0;
                    return `background-image: url('${this.imagemUrl}'); background-size: ${this.colunas * 100}% ${this.linhas * 100}%; background-position: ${x}% ${y}%;`;
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
                    const anterior = this.lerRecorde();
                    if (anterior === null || this.segundos < anterior) {
                        this.recorde = true;
                        this.melhor = this.segundos;
                        try { localStorage.setItem(this.chaveRecorde(), String(this.segundos)); } catch (e) {}
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
