<x-Terminal::layout>
    {{-- Lógica pura do jogo (níveis, gerador, solucionador). Sem acesso ao DOM: é carregada também pelos testes em Node. --}}
    <script>
        // TERMINAL-LOGICA-INICIO
        const SIMBOLOS = ['●', '■', '▲', '◆', '★', '✚'];
        const MAX_ONIBUS = 8;

        // Nível 1 (intro, do design): cada tipo tem um ônibus de 3 lugares e 3 passageiros.
        const NIVEL_1 = {
            vagas: 2,
            fila: '●●■■●■',
            onibus: [{ t: '●', cap: 3 }, { t: '■', cap: 3 }, { t: '▲', cap: 3 }],
        };

        // A partir do nível 2: vagas, tipos de passageiro, passageiros por tipo, ônibus-isca, limite de vitória
        // ao acaso (pMax) e quantas heurísticas simples ("sempre o menor/maior") precisam falhar.
        const PROGRESSAO = [
            { vagas: 2, tipos: 3, nMin: 3, nMax: 4, extras: 1, pMax: 0.7, falhas: 1 },
            { vagas: 2, tipos: 3, nMin: 3, nMax: 5, extras: 1, pMax: 0.5, falhas: 1 },
            { vagas: 3, tipos: 4, nMin: 3, nMax: 4, extras: 1, pMax: 0.5, falhas: 1 },
            { vagas: 3, tipos: 4, nMin: 3, nMax: 5, extras: 1, pMax: 0.4, falhas: 2 },
            { vagas: 3, tipos: 5, nMin: 3, nMax: 4, extras: 1, pMax: 0.35, falhas: 2 },
            { vagas: 4, tipos: 5, nMin: 3, nMax: 4, extras: 1, pMax: 0.3, falhas: 2 },
            { vagas: 4, tipos: 5, nMin: 3, nMax: 5, extras: 1, pMax: 0.25, falhas: 2 },
            { vagas: 4, tipos: 6, nMin: 3, nMax: 4, extras: 0, pMax: 0.35, falhas: 1 },
            { vagas: 5, tipos: 6, nMin: 3, nMax: 4, extras: 0, pMax: 0.3, falhas: 1 },
        ];

        function criarRng(semente) {
            let a = semente >>> 0;
            return () => {
                a = (a + 0x6D2B79F5) >>> 0;
                let t = a;
                t = Math.imul(t ^ (t >>> 15), t | 1);
                t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
                return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
            };
        }

        // Em qual vaga embarca quem está na frente: ônibus do tipo com lugar, o mais cheio; empate, a menor vaga.
        function vagaDeEmbarque(vagas, tipo) {
            let melhor = -1;
            vagas.forEach((v, i) => {
                if (v && v.t === tipo && v.n < v.cap && (melhor < 0 || v.n > vagas[melhor].n)) melhor = i;
            });
            return melhor;
        }

        function estadoInicial(nivel) {
            return {
                i: 0,
                vagas: Array.from({ length: nivel.vagas }, () => null),
                livres: nivel.onibus.map((o) => ({ t: o.t, cap: o.cap })),
            };
        }

        // Embarca em sequência até a fila esvaziar ou o da frente não ter onde entrar.
        function embarcarTudo(nivel, estado) {
            const e = { i: estado.i, vagas: estado.vagas.map((v) => (v ? { ...v } : null)), livres: estado.livres };
            while (e.i < nivel.fila.length) {
                const v = vagaDeEmbarque(e.vagas, nivel.fila[e.i]);
                if (v < 0) break;
                e.i++;
                e.vagas[v].n++;
                if (e.vagas[v].n >= e.vagas[v].cap) e.vagas[v] = null;
            }
            return e;
        }

        // 'vitoria' | 'bloqueado' | 'jogando'
        function situacao(nivel, e) {
            if (e.i >= nivel.fila.length) return 'vitoria';
            const t = nivel.fila[e.i];
            if (e.vagas.indexOf(null) < 0) return 'bloqueado';
            if (!e.livres.some((o) => o.t === t)) return 'bloqueado';
            return 'jogando';
        }

        // Escolhas distintas (tipo+capacidade) para quem está na frente da fila.
        function escolhas(nivel, e) {
            const t = nivel.fila[e.i];
            const vistos = new Set();
            const r = [];
            e.livres.forEach((o, idx) => {
                const k = o.t + o.cap;
                if (o.t === t && !vistos.has(k)) { vistos.add(k); r.push(idx); }
            });
            return r;
        }

        // Estaciona o ônibus `idx` de `livres` na primeira vaga livre e embarca.
        function jogar(nivel, e, idx) {
            const vagas = e.vagas.map((v) => (v ? { ...v } : null));
            const o = e.livres[idx];
            vagas[vagas.indexOf(null)] = { t: o.t, cap: o.cap, n: 0 };
            const livres = e.livres.filter((_, k) => k !== idx);
            return embarcarTudo(nivel, { i: e.i, vagas, livres });
        }

        function chave(e) {
            const p = e.vagas.filter(Boolean).map((v) => v.t + v.cap + ':' + v.n).sort().join(',');
            const l = e.livres.map((o) => o.t + o.cap).sort().join(',');
            return e.i + '|' + p + '|' + l;
        }

        // Avaliador memorizado: de um estado, { p: chance de vencer escolhendo ao acaso, sol: existe caminho vencedor }.
        function criarAvaliador(nivel) {
            const memo = new Map();
            const rec = (e) => {
                const s = situacao(nivel, e);
                if (s === 'vitoria') return { p: 1, sol: true };
                if (s === 'bloqueado') return { p: 0, sol: false };
                const k = chave(e);
                if (memo.has(k)) return memo.get(k);
                const opcoes = escolhas(nivel, e);
                let soma = 0, sol = false;
                for (const idx of opcoes) {
                    const r = rec(jogar(nivel, e, idx));
                    soma += r.p;
                    sol = sol || r.sol;
                }
                const res = { p: opcoes.length ? soma / opcoes.length : 0, sol };
                memo.set(k, res);
                return res;
            };
            return rec;
        }

        // Solucionável? Chance de vencer escolhendo ao acaso? (jogadas preguiçosas: pré-estacionar nunca ajuda)
        function analisar(nivel) {
            const r = criarAvaliador(nivel)(embarcarTudo(nivel, estadoInicial(nivel)));
            return { solucionavel: r.sol, pAleatorio: r.p };
        }

        // Dica: a escolha ({ t, cap }) com mais chance de vitória a partir do estado `e`; null se não houver saída.
        // `e` = { i: passageiros já embarcados, vagas: [null | { t, cap, n }], livres: [{ t, cap }] }.
        function melhorEscolha(nivel, e) {
            if (situacao(nivel, e) !== 'jogando') return null;
            const rec = criarAvaliador(nivel);
            let melhor = null;
            for (const idx of escolhas(nivel, e)) {
                const r = rec(jogar(nivel, e, idx));
                if (r.sol && (!melhor || r.p > melhor.p)) melhor = { t: e.livres[idx].t, cap: e.livres[idx].cap, p: r.p };
            }
            return melhor;
        }

        // Simula "sempre o ônibus de menor (ou maior) capacidade do tipo da frente".
        function heuristica(nivel, modo) {
            let e = embarcarTudo(nivel, estadoInicial(nivel));
            for (;;) {
                const s = situacao(nivel, e);
                if (s !== 'jogando') return s === 'vitoria';
                const op = escolhas(nivel, e).sort((a, b) => e.livres[a].cap - e.livres[b].cap);
                e = jogar(nivel, e, modo === 'menor' ? op[0] : op[op.length - 1]);
            }
        }

        function gerarComConfig(cfg, base, rotulo) {
            const tipos = SIMBOLOS.slice(0, cfg.tipos);
            for (let tentativa = 0; tentativa < 20000; tentativa++) {
                const rng = criarRng(base + tentativa);
                const inteiro = (a, b) => a + Math.floor(rng() * (b - a + 1));
                const passageiros = [];
                const onibus = [];
                for (const t of tipos) {
                    const n = inteiro(cfg.nMin, cfg.nMax);
                    for (let k = 0; k < n; k++) passageiros.push(t);
                    let resta = n;
                    while (resta > 0) {
                        let cap = resta <= 4 && rng() < 0.45 ? resta : inteiro(2, 4);
                        if (resta - cap === 1) cap = resta < 4 ? resta : 3;
                        cap = Math.min(cap, resta);
                        if (cap < 2) cap = 2;
                        onibus.push({ t, cap });
                        resta -= cap;
                    }
                }
                for (let k = 0; k < cfg.extras; k++) onibus.push({ t: tipos[inteiro(0, tipos.length - 1)], cap: inteiro(2, 4) });
                if (onibus.length > MAX_ONIBUS || onibus.length <= cfg.vagas) continue;
                for (let i = passageiros.length - 1; i > 0; i--) {
                    const j = Math.floor(rng() * (i + 1));
                    [passageiros[i], passageiros[j]] = [passageiros[j], passageiros[i]];
                }
                onibus.sort((a, b) => SIMBOLOS.indexOf(a.t) - SIMBOLOS.indexOf(b.t) || a.cap - b.cap);
                const nivel = { vagas: cfg.vagas, fila: passageiros.join(''), onibus };
                const a = analisar(nivel);
                if (!a.solucionavel || a.pAleatorio > cfg.pMax) continue;
                const falhas = ['menor', 'maior'].filter((m) => !heuristica(nivel, m)).length;
                if (falhas < cfg.falhas) continue;
                return nivel;
            }
            throw new Error('Não foi possível gerar o nível ' + rotulo);
        }

        function gerarNivel(indice) {
            return gerarComConfig(PROGRESSAO[Math.min(indice - 1, PROGRESSAO.length - 1)], indice * 7919, indice + 1);
        }

        function obterNivel(indice) {
            return indice === 0 ? NIVEL_1 : gerarNivel(indice);
        }

        // Desafio diário: `data` = 'AAAA-MM-DD'. Mesma fila para todos no mesmo dia; a dificuldade gira (linhas 3 a 9 da progressão).
        function gerarNivelDiario(data) {
            const n = parseInt(String(data).replace(/-/g, ''), 10);
            return gerarComConfig(PROGRESSAO[2 + (n % 7)], n * 104729, data);
        }

        // 3 estrelas; perde uma por ter usado dica e outra por ter falhado antes de concluir (mínimo 1).
        function estrelasDaFase(dicas, falhas) {
            return Math.max(1, 3 - (dicas > 0 ? 1 : 0) - (falhas > 0 ? 1 : 0));
        }

        // Conquistas que o contexto merece. ctx: { diario, modo, fases (concluídas), tresEstrelas (qtde), estrelasFase, sequencia }.
        function conquistasElegiveis(ctx) {
            const r = [];
            if (ctx.diario) {
                r.push('PONTUAL');
                if (ctx.sequencia >= 3) r.push('HABITUE');
                if (ctx.sequencia >= 7) r.push('VETERANO');
                return r;
            }
            if (ctx.fases >= 1) r.push('BOOT');
            if (ctx.estrelasFase === 3) r.push('ZERO_WASTE');
            if (ctx.tresEstrelas >= 5) r.push('PLENO');
            if (ctx.fases >= 10) r.push('MARATONISTA');
            if (ctx.modo === 'hard') r.push('TECLADISTA');
            return r;
        }
        // TERMINAL-LOGICA-FIM
    </script>

    <script>
        const ARTE_ONIBUS = [
            ' _________________ ',
            '|                 |\\',
            '|                 | |',
            '|_________________|_|',
            '  (O)         (O)   ',
        ].join('\n');

        // Em vagas baixas (4–5 vagas) o ônibus usa 3 linhas em vez de 5; os passageiros ficam na linha do meio.
        const ARTE_COMPACTA = [
            ' _________________ ',
            '|                 |\\',
            '|_(O)_________(O)_|_|',
        ].join('\n');

        const TECLADO = [
            { pad: 0, teclas: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'DEL'] },
            { pad: 0, teclas: ['SIMB', 'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'] },
            { pad: 8, teclas: ['CAPS', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L'] },
            { pad: 0, teclas: ['SHIFT', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 'ENTER'] },
            { pad: 48, teclas: [' '] },
        ];

        const MODOS = ['normal', 'hard'];
        const FOSFOROS = ['verde', 'ambar', 'branco', 'ciano', 'vermelho', 'papel'];
        const NOME_FOSFORO = { verde: 'VERDE', ambar: 'AMBAR', branco: 'BRANCO', ciano: 'CIANO', vermelho: 'VERMELHO', papel: 'PAPEL' };
        // Intensidade das scanlines (alfa das linhas escuras).
        const SCANS = [['off', 0, 'OFF'], ['leve', 0.12, 'LEVE'], ['normal', 0.24, 'NORMAL'], ['forte', 0.4, 'FORTE']];
        const PENALIDADE_DICA = 10000;
        const HOJE = @json($hoje);
        const USUARIO = @json(auth()->user()->name);

        // Nome e descrição das conquistas (os códigos válidos ficam em TerminalController::CONQUISTAS).
        const CONQUISTAS = [
            { c: 'BOOT', nome: 'BOOT SEQUENCE', desc: 'CONCLUA A PRIMEIRA FASE' },
            { c: 'ZERO_WASTE', nome: 'ZERO WASTE', desc: '3 ESTRELAS NUMA FASE' },
            { c: 'PLENO', nome: 'FUNCIONARIO PADRAO', desc: '3 ESTRELAS EM 5 FASES' },
            { c: 'MARATONISTA', nome: 'HORA EXTRA', desc: 'CONCLUA 10 FASES' },
            { c: 'TECLADISTA', nome: 'DATILOGRAFO', desc: 'CONCLUA UMA FASE NO HARD' },
            { c: 'PONTUAL', nome: 'PONTO BATIDO', desc: 'CONCLUA O DESAFIO DIARIO' },
            { c: 'HABITUE', nome: 'EXPEDIENTE FIXO', desc: '3 DIAS SEGUIDOS NO DIARIO' },
            { c: 'VETERANO', nome: 'CRACHA DOURADO', desc: '7 DIAS SEGUIDOS NO DIARIO' },
            { c: 'HACKER', nome: 'ACESSO ROOT', desc: 'TENTOU SUDO NO CONSOLE', secreta: true },
        ];

        function guardar(chave, valor) {
            try { localStorage.setItem('terminal:' + chave, String(valor)); } catch (e) { /* sem armazenamento */ }
        }

        function ler(chave, padrao) {
            try { return localStorage.getItem('terminal:' + chave) ?? padrao; } catch (e) { return padrao; }
        }

        function terminal() {
            return {
                arteGrande: ARTE_ONIBUS,
                arteCompacta: ARTE_COMPACTA,
                alturaVaga: 200,
                teclado: TECLADO,
                fosforo: 'verde',
                scan: 'normal',
                boot: true,
                bootLinhas: [],
                bootRun: 0,
                mudo: false,
                diario: false,
                nivelDoDiaCache: null,
                diarioInfo: null,
                estrelasMapa: {},
                conquistas: [],
                falhas: 0,
                chaveFalhas: '',
                tela: 'home',
                rank: null,
                rankErro: false,
                abaRank: 'fase',
                faseRank: 1,
                confirmaReboot: false,
                recorde: false,
                simbolos: false,
                modo: 'normal',
                nivel: 0,
                g: null,
                audio: null,

                get compacto() { return this.alturaVaga < 92; },
                get escala() { return Math.min(1, Math.max(0.4, (this.alturaVaga - 6) / (this.compacto ? 48 : 80))); },
                get nomeFosforo() { return NOME_FOSFORO[this.fosforo]; },
                get scanAlfa() { return (SCANS.find((x) => x[0] === this.scan) || SCANS[2])[1]; },
                get nomeScan() { return (SCANS.find((x) => x[0] === this.scan) || SCANS[2])[2]; },
                get catalogo() { return CONQUISTAS; },
                get fasesLiberadas() { return Array.from({ length: this.nivelSalvo(this.modo) + 1 }, (_, k) => k + 1); },
                get totalEstrelas() { return Object.values(this.estrelasMapa).reduce((a, b) => a + b, 0); },
                get tituloNivel() {
                    return this.diario ? 'DIARIO ' + HOJE.slice(8) + '/' + HOJE.slice(5, 7) : 'NIVEL ' + this.numeroNivel;
                },
                get fino() { return this.alturaVaga < 64; },
                get botoesPequenos() { return this.g.onibus.length > 4; },

                // Mede a altura de uma vaga para escolher a arte do ônibus e o zoom.
                medir() {
                    const vaga = this.$refs.vagas && this.$refs.vagas.querySelector('[data-vaga]');
                    if (vaga && vaga.clientHeight > 0) this.alturaVaga = vaga.clientHeight;
                },

                // O progresso é separado por modo; a chave antiga 'nivel' pertencia ao modo normal.
                nivelSalvo(modo) {
                    const salvo = parseInt(ler('nivel:' + modo, modo === 'normal' ? ler('nivel', '0') : '0'), 10);
                    return Number.isInteger(salvo) && salvo >= 0 ? salvo : 0;
                },

                trocarModo(modo) {
                    if (modo === this.modo) return;
                    this.modo = modo;
                    guardar('modo', modo);
                    this.nivel = this.nivelSalvo(modo);
                    this.faseRank = this.nivel + 1;
                    this.rank = null;
                    this.reiniciar(this.nivel);
                    this.carregarRanking();
                },

                init() {
                    const fosforo = ler('fosforo', 'verde');
                    this.fosforo = FOSFOROS.includes(fosforo) ? fosforo : 'verde';
                    this.mudo = ler('mudo', '0') === '1';
                    this.boot = ler('boot', '1') === '1';
                    const scan = ler('scan', 'normal');
                    this.scan = SCANS.some((x) => x[0] === scan) ? scan : 'normal';
                    const modo = ler('modo', 'normal');
                    this.modo = MODOS.includes(modo) ? modo : 'normal';
                    this.nivel = this.nivelSalvo(this.modo);
                    this.faseRank = this.nivel + 1;
                    this.novoJogo(this.nivel, 0);
                    this.carregarRanking();
                    window.addEventListener('keydown', (e) => this.teclaFisica(e));
                    if (this.boot && !this.bootVisto() && !(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) {
                        this.tela = 'boot';
                        this.rodarBoot();
                    }
                    this.medirDepois();
                    this.$nextTick(() => {
                        if (window.ResizeObserver && this.$refs.vagas) new ResizeObserver(() => this.medir()).observe(this.$refs.vagas);
                    });
                },

                nivelDeHoje() {
                    if (!this.nivelDoDiaCache) this.nivelDoDiaCache = gerarNivelDiario(HOJE);
                    return this.nivelDoDiaCache;
                },

                novoJogo(nivel, run) {
                    const n = this.diario ? this.nivelDeHoje() : obterNivel(nivel);
                    if (!this.diario) {
                        this.nivel = nivel;
                        // Rejogar uma fase antiga não faz o progresso recuar.
                        if (nivel > this.nivelSalvo(this.modo)) guardar('nivel:' + this.modo, nivel);
                    }
                    const chaveFalhas = this.modo + ':' + (this.diario ? 'd' + HOJE : nivel);
                    if (chaveFalhas !== this.chaveFalhas) {
                        this.chaveFalhas = chaveFalhas;
                        this.falhas = 0;
                    }
                    this.g = {
                        run,
                        def: { vagas: n.vagas, fila: n.fila, onibus: n.onibus.map((o) => ({ t: o.t, cap: o.cap })) },
                        dicas: 0,
                        penalidade: 0,
                        dica: null,
                        estrelas: 0,
                        novas: [],
                        fila: n.fila.split(''),
                        onibus: n.onibus.map((o, id) => ({ id, t: o.t, cap: o.cap, st: 'livre' })),
                        vagas: Array.from({ length: n.vagas }, () => null),
                        cmd: '',
                        msgs: ['READY.', (this.modo === 'hard' ? 'DIGITE: BUS ' : 'TOQUE OU DIGITE: BUS ') + n.onibus[0].t + n.onibus[0].cap],
                        ocupado: false,
                        status: 'jogando',
                        tecla: null,
                        t0: null,
                        tempo: null,
                    };
                    this.simbolos = false;
                    this.recorde = false;
                },

                reiniciar(nivel) {
                    this.novoJogo(nivel, this.g.run + 1);
                    this.medirDepois();
                },

                medirDepois() {
                    this.$nextTick(() => requestAnimationFrame(() => this.medir()));
                },

                get numeroNivel() { return String(this.nivel + 1).padStart(2, '0'); },
                get parado() { return this.g.ocupado || this.g.status !== 'jogando'; },

                trocarFosforo() {
                    this.fosforo = FOSFOROS[(FOSFOROS.indexOf(this.fosforo) + 1) % FOSFOROS.length];
                    guardar('fosforo', this.fosforo);
                },

                // --- boot de BIOS (uma vez por sessão do navegador; qualquer toque/tecla pula) ---
                bootVisto() {
                    try { return sessionStorage.getItem('terminal:boot') === '1'; } catch (e) { return false; }
                },

                alternarBoot() {
                    this.boot = !this.boot;
                    guardar('boot', this.boot ? '1' : '0');
                },

                pularBoot() {
                    if (this.tela !== 'boot') return;
                    this.bootRun++;
                    this.encerrarBoot();
                },

                encerrarBoot() {
                    try { sessionStorage.setItem('terminal:boot', '1'); } catch (e) { /* sem armazenamento */ }
                    this.tela = 'home';
                },

                async rodarBoot() {
                    const run = ++this.bootRun;
                    const vivo = () => this.tela === 'boot' && this.bootRun === run;
                    const linha = async (texto, ms) => {
                        if (!vivo()) return false;
                        this.bootLinhas.push(texto);
                        this.bip(900, 0.02, 'square', 0.015);
                        await this.espera(ms);
                        return vivo();
                    };
                    this.bootLinhas = [];
                    if (!(await linha('TERMINAL BIOS V1.0', 250))) return;
                    if (!(await linha('(C) 1987 PORTAL SYSTEMS', 250))) return;
                    if (!(await linha('', 150))) return;
                    if (!(await linha('MEMORIA: 0K', 40))) return;
                    const m = this.bootLinhas.length - 1;
                    for (let k = 64; k <= 640; k += 64) {
                        this.bootLinhas[m] = 'MEMORIA: ' + k + 'K';
                        this.bip(300 + k, 0.015, 'square', 0.01);
                        await this.espera(45);
                        if (!vivo()) return;
                    }
                    this.bootLinhas[m] = 'MEMORIA: 640K OK';
                    for (const t of ['TECLADO', 'MONITOR CRT', 'GARAGEM', 'FILA DE PASSAGEIROS']) {
                        if (!(await linha(t.padEnd(24, '.') + ' OK', 260))) return;
                    }
                    if (!(await linha('', 120))) return;
                    if (!(await linha('USUARIO: ' + USUARIO.toUpperCase(), 350))) return;
                    if (!(await linha('INICIANDO TERMINAL...', 600))) return;
                    this.encerrarBoot();
                },

                trocarScan() {
                    this.scan = SCANS[(SCANS.findIndex((x) => x[0] === this.scan) + 1) % SCANS.length][0];
                    guardar('scan', this.scan);
                },

                alternarSom() {
                    this.mudo = !this.mudo;
                    guardar('mudo', this.mudo ? '1' : '0');
                },

                // --- tela inicial e ranking ---
                jogar() {
                    this.diario = false;
                    this.tela = 'jogo';
                    this.confirmaReboot = false;
                    this.bip(520, 0.05);
                    this.reiniciar(this.nivel);
                },

                jogarDiario() {
                    this.diario = true;
                    this.tela = 'jogo';
                    this.bip(520, 0.05);
                    this.reiniciar(this.nivel);
                },

                escolherFase(fase) {
                    this.diario = false;
                    this.tela = 'jogo';
                    this.bip(520, 0.05);
                    this.reiniciar(fase - 1);
                },

                abrirRank() {
                    this.faseRank = this.nivel + 1;
                    this.tela = 'rank';
                    this.carregarRanking();
                },

                abrirMenu() {
                    this.diario = false;
                    this.reiniciar(this.nivel);
                    this.tela = 'home';
                    this.faseRank = this.nivel + 1;
                    this.carregarRanking();
                },

                // Volta ao nível 1 (o ranking no servidor não é afetado); pede um segundo toque para confirmar.
                rebootGeral() {
                    if (!this.confirmaReboot) {
                        this.confirmaReboot = true;
                        setTimeout(() => { this.confirmaReboot = false; }, 3000);
                        return;
                    }
                    this.confirmaReboot = false;
                    this.faseRank = 1;
                    guardar('nivel:' + this.modo, 0);
                    this.reiniciar(0);
                    this.carregarRanking();
                },

                async carregarRanking() {
                    try {
                        const resp = await fetch('{{ route('terminal.ranking') }}?modo=' + this.modo + '&fase=' + this.faseRank, { headers: { Accept: 'application/json' } });
                        if (!resp.ok) throw new Error(resp.status);
                        this.rank = await resp.json();
                        this.faseRank = this.rank.fase;
                        this.estrelasMapa = { ...this.rank.estrelas };
                        this.conquistas = [...this.rank.conquistas];
                        this.rankErro = false;
                    } catch (e) { this.rankErro = true; }
                    this.carregarDiario();
                },

                async carregarDiario() {
                    try {
                        const resp = await fetch('{{ route('terminal.diario') }}?modo=' + this.modo, { headers: { Accept: 'application/json' } });
                        if (!resp.ok) throw new Error(resp.status);
                        this.diarioInfo = await resp.json();
                    } catch (e) { /* sem rede: o diário continua jogável */ }
                },

                trocarFaseRank(passo) {
                    const max = this.rank ? this.rank.fase_maxima : 1;
                    const nova = Math.min(max, Math.max(1, this.faseRank + passo));
                    if (nova === this.faseRank) return;
                    this.faseRank = nova;
                    this.carregarRanking();
                },

                get linhasRank() {
                    if (this.abaRank === 'diario') return this.diarioInfo ? this.diarioInfo.ranking : [];
                    if (!this.rank) return [];
                    return { fase: this.rank.por_fase, geral: this.rank.geral, fases: this.rank.solucionadores, diario: this.diarioInfo ? this.diarioInfo.ranking : [] }[this.abaRank];
                },

                get rankPronto() { return this.abaRank === 'diario' ? !!this.diarioInfo : !!this.rank; },

                formatarTempo(ms) {
                    const t = Math.floor(ms / 100);
                    const dec = t % 10;
                    const seg = Math.floor(t / 10) % 60;
                    const min = Math.floor(t / 600);
                    return String(min).padStart(2, '0') + ':' + String(seg).padStart(2, '0') + '.' + dec;
                },

                async postar(rota, corpo) {
                    const token = document.querySelector('meta[name="csrf-token"]');
                    const resp = await fetch(rota, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' },
                        body: JSON.stringify(corpo),
                    });
                    if (!resp.ok) throw new Error(resp.status);
                    return resp.json();
                },

                async enviarResultado(fase, tempo, estrelas) {
                    try {
                        const r = await this.postar('{{ route('terminal.registrar') }}', { modo: this.modo, fase, tempo_ms: tempo, estrelas });
                        this.recorde = r.recorde;
                        this.estrelasMapa = { ...this.estrelasMapa, [fase]: r.estrelas };
                        await this.checarConquistas({ diario: false, estrelasFase: estrelas });
                    } catch (e) { /* sem rede: o jogo segue, o tempo não entra no ranking */ }
                },

                async enviarDiario(tempo) {
                    try {
                        const r = await this.postar('{{ route('terminal.diario.registrar') }}', { modo: this.modo, data: HOJE, tempo_ms: tempo });
                        this.recorde = r.recorde;
                        if (this.diarioInfo) this.diarioInfo.sequencia = r.sequencia;
                        await this.checarConquistas({ diario: true, sequencia: r.sequencia });
                        this.carregarDiario();
                    } catch (e) { /* sem rede */ }
                },

                // --- conquistas ---
                async checarConquistas(extra) {
                    const estrelas = Object.values(this.estrelasMapa);
                    const ctx = { diario: false, modo: this.modo, fases: estrelas.length, tresEstrelas: estrelas.filter((v) => v === 3).length, estrelasFase: 0, sequencia: 0, ...extra };
                    for (const codigo of conquistasElegiveis(ctx)) await this.desbloquear(codigo);
                },

                // Devolve true se a conquista é nova (e já avisa o jogador).
                async desbloquear(codigo) {
                    if (this.conquistas.includes(codigo)) return false;
                    try {
                        const r = await this.postar('{{ route('terminal.conquista') }}', { codigo });
                        this.conquistas.push(codigo);
                        if (!r.novo) return false;
                        const info = CONQUISTAS.find((x) => x.c === codigo);
                        this.g.novas.push(info.nome);
                        this.bip(880, 0.12, 'triangle', 0.05);
                        return true;
                    } catch (e) { return false; }
                },

                // --- dica ---
                estadoParaDica() {
                    const g = this.g;
                    return {
                        i: g.def.fila.length - g.fila.length,
                        vagas: g.vagas.map((v) => (v ? { t: v.t, cap: v.cap, n: v.n } : null)),
                        livres: g.onibus.filter((b) => b.st === 'livre').map((b) => ({ t: b.t, cap: b.cap })),
                    };
                },

                pedirDica() {
                    if (this.tela !== 'jogo' || this.parado) return;
                    const g = this.g;
                    g.cmd = '';
                    if (g.dica) {
                        g.msgs = ['DICA: BUS ' + g.dica.t + g.dica.cap, 'JA PAGA: ESCOLHA ESSE ONIBUS'];
                        return;
                    }
                    const melhor = melhorEscolha(g.def, this.estadoParaDica());
                    if (!melhor) {
                        g.msgs = ['SEM SAIDA NESTE ESTADO', 'USE REBOOT PARA TENTAR DE NOVO'];
                        this.bip(140, 0.25, 'sawtooth');
                        return;
                    }
                    g.dica = { t: melhor.t, cap: melhor.cap };
                    g.dicas++;
                    g.penalidade += PENALIDADE_DICA;
                    g.msgs = ['DICA: BUS ' + melhor.t + melhor.cap, '+10S NO TEMPO · -1 ESTRELA'];
                    this.bip(660, 0.12, 'triangle', 0.05);
                },

                // --- comandos de "shell" (brincadeira): devolve true se o texto era um deles ---
                comandoShell(texto) {
                    const g = this.g;
                    const cmd = texto.split(/\s+/)[0];
                    let linhas = null;
                    switch (cmd) {
                        case 'HELP': case 'AJUDA':
                            linhas = ['COMANDOS: BUS <SIMB><N> · HINT · STATS', 'WHOAMI · DATE · LS · COR · SOM · SCAN · REBOOT']; break;
                        case 'HINT': case 'DICA':
                            this.pedirDica(); return true;
                        case 'STATS':
                            linhas = ['FASES: ' + Object.keys(this.estrelasMapa).length + ' · ESTRELAS: ' + this.totalEstrelas + ' · CONQUISTAS: ' + this.conquistas.length,
                                'NESTA FASE: ' + g.dicas + ' DICA(S) · ' + this.falhas + ' FALHA(S)']; break;
                        case 'WHOAMI':
                            linhas = ['USER: ' + USUARIO.toUpperCase(), 'CARGO: FUNCIONARIO DO MES (EM TESTE)']; break;
                        case 'DATE':
                            linhas = [new Date().toLocaleString('pt-BR'), 'FUSO: ONDE O ONIBUS ESTIVER']; break;
                        case 'LS':
                            linhas = ['BUS/  FILA/  VAGAS/  PASSAGEIROS.TXT', 'LEIAME.TXT  SEGREDOS/ (ACESSO NEGADO)']; break;
                        case 'PING':
                            linhas = ['PONG', 'LATENCIA: 0MS (ONIBUS NAO TEM LATENCIA)']; break;
                        case 'COR':
                            this.trocarFosforo(); linhas = ['COR: ' + this.nomeFosforo]; break;
                        case 'SOM':
                            this.alternarSom(); linhas = ['SOM: ' + (this.mudo ? 'OFF' : 'ON')]; break;
                        case 'SCAN':
                            this.trocarScan(); linhas = ['SCANLINES: ' + this.nomeScan]; break;
                        case 'MENU': case 'LOGOUT': case 'EXIT': case 'QUIT':
                            this.abrirMenu(); return true;
                        case 'REBOOT':
                            this.abrirMenu();
                            this.tela = 'boot';
                            this.rodarBoot();
                            return true;
                        case 'HACK':
                            linhas = ['ACESSANDO MAINFRAME... [####....]', 'ERRO: O MAINFRAME E UM ONIBUS']; break;
                        case 'RM':
                            linhas = ['RM: ACESSO NEGADO', 'ONIBUS NAO SE APAGAM']; break;
                        case 'SUDO':
                            linhas = ['PERMISSION DENIED', 'ESTE INCIDENTE SERA REPORTADO (BRINCADEIRA)'];
                            this.desbloquear('HACKER').then((novo) => { if (novo) this.g.msgs = ['ACHIEVEMENT UNLOCKED:', 'ACESSO ROOT']; });
                            break;
                        default:
                            return false;
                    }
                    g.cmd = '';
                    g.msgs = linhas;
                    this.bip(520, 0.05);
                    return true;
                },

                // --- som (WebAudio, só depois do primeiro toque) ---
                bip(freq, dur, tipo = 'square', vol = 0.04) {
                    if (this.mudo) return;
                    try {
                        this.audio = this.audio || new (window.AudioContext || window.webkitAudioContext)();
                        const ac = this.audio;
                        const osc = ac.createOscillator();
                        const ganho = ac.createGain();
                        osc.type = tipo;
                        osc.frequency.value = freq;
                        ganho.gain.setValueAtTime(vol, ac.currentTime);
                        ganho.gain.exponentialRampToValueAtTime(0.0001, ac.currentTime + dur);
                        osc.connect(ganho).connect(ac.destination);
                        osc.start();
                        osc.stop(ac.currentTime + dur);
                    } catch (e) { /* sem áudio */ }
                },

                // --- animação ---
                vivo(run) { return this.g.run === run; },
                espera(ms) { return new Promise((r) => setTimeout(r, ms)); },

                async apertar(run, tecla, ms) {
                    this.g.tecla = tecla;
                    this.bip(tecla === 'ENTER' ? 320 : 520, 0.05);
                    await this.espera(ms);
                    if (!this.vivo(run)) return false;
                    this.g.tecla = null;
                    await this.espera(35);
                    return this.vivo(run);
                },

                async digitar(run, texto) {
                    this.g.cmd = '';
                    for (const ch of texto) {
                        this.g.cmd += ch;
                        if (!(await this.apertar(run, ch, 100))) return false;
                    }
                    return true;
                },

                async escolher(id) {
                    const g = this.g;
                    if (this.parado || this.modo === 'hard') return;
                    const bus = g.onibus[id];
                    if (bus.st !== 'livre') return;
                    const vaga = g.vagas.indexOf(null);
                    if (vaga < 0) {
                        g.msgs = ['> BUS ' + bus.t + bus.cap, 'SEM VAGA LIVRE'];
                        this.bip(140, 0.25, 'sawtooth');
                        return;
                    }
                    const run = g.run;
                    g.ocupado = true;
                    this.simbolos = false;
                    if (g.t0 === null) g.t0 = performance.now();
                    if (!(await this.digitar(run, 'BUS'))) return;
                    g.cmd += ' ' + bus.t + bus.cap;
                    if (!(await this.apertar(run, 'ENTER', 160))) return;
                    await this.estacionar(run, bus, vaga);
                },

                async estacionar(run, bus, vaga) {
                    const g = this.g;
                    g.cmd = '';
                    g.dica = null;
                    g.msgs = ['> BUS ' + bus.t + bus.cap, 'BOARDING...'];
                    bus.st = 'estacionado';
                    g.vagas[vaga] = { id: bus.id, t: bus.t, cap: bus.cap, n: 0, x: 'esq' };
                    await this.espera(60); if (!this.vivo(run)) return;
                    g.vagas[vaga].x = 'dentro';
                    this.bip(110, 0.7, 'sawtooth', 0.03);
                    await this.espera(850); if (!this.vivo(run)) return;
                    if (!(await this.embarcar(run))) return;
                    await this.concluir(run);
                },

                // --- teclado: o jogador pode digitar "BUS <símbolo><lugares>" e dar ENTER ---
                get simbolosNivel() {
                    return [...new Set(this.g.onibus.map((b) => b.t))];
                },

                teclar(tecla) {
                    if (this.tela !== 'jogo' || this.parado) return;
                    const g = this.g;
                    g.tecla = tecla;
                    setTimeout(() => { if (g.tecla === tecla) g.tecla = null; }, 110);
                    this.bip(tecla === 'ENTER' ? 320 : 520, 0.05);
                    if (tecla === 'ENTER') { this.simbolos = false; this.enviarComando(); return; }
                    if (tecla === 'SIMB') { this.simbolos = !this.simbolos; return; }
                    if (tecla === 'DEL') { g.cmd = g.cmd.slice(0, -1); return; }
                    if (tecla === ' ') { if (g.cmd && !g.cmd.endsWith(' ')) g.cmd += ' '; return; }
                    if (g.cmd.length < 16) g.cmd += tecla;
                },

                inserirSimbolo(simbolo) {
                    if (this.parado) return;
                    if (this.g.cmd.length < 16) this.g.cmd += simbolo;
                    this.simbolos = false;
                    this.bip(520, 0.05);
                },

                // Teclado físico: Tab insere (ou troca pelo próximo) símbolo.
                teclaFisica(e) {
                    if (this.tela === 'boot') { e.preventDefault(); this.pularBoot(); return; }
                    if (this.tela !== 'jogo' || this.parado || e.ctrlKey || e.metaKey || e.altKey) return;
                    const g = this.g;
                    let tecla = null;
                    if (e.key === 'Enter') tecla = 'ENTER';
                    else if (e.key === 'Backspace') tecla = 'DEL';
                    else if (e.key === ' ') tecla = ' ';
                    else if (/^[a-z0-9]$/i.test(e.key)) tecla = e.key.toUpperCase();
                    else if (e.key === 'Tab') {
                        e.preventDefault();
                        const s = this.simbolosNivel;
                        const i = s.indexOf(g.cmd.slice(-1));
                        if (i >= 0) g.cmd = g.cmd.slice(0, -1) + s[(i + 1) % s.length];
                        else if (g.cmd.length < 16) g.cmd += s[0];
                        return;
                    }
                    if (tecla === null) return;
                    e.preventDefault();
                    this.teclar(tecla);
                },

                async enviarComando() {
                    const g = this.g;
                    const texto = g.cmd.trim();
                    if (!texto) return;
                    if (this.comandoShell(texto)) return;
                    const m = texto.match(/^BUS\s+(\S)(\d)$/u);
                    if (!m) {
                        g.cmd = '';
                        g.msgs = ['> ' + texto, 'COMANDO INVALIDO'];
                        this.bip(140, 0.25, 'sawtooth');
                        return;
                    }
                    const bus = g.onibus.find((b) => b.st === 'livre' && b.t === m[1] && b.cap === Number(m[2]));
                    if (!bus) {
                        g.cmd = '';
                        g.msgs = ['> ' + texto, 'ONIBUS NAO ENCONTRADO'];
                        this.bip(140, 0.25, 'sawtooth');
                        return;
                    }
                    const vaga = g.vagas.indexOf(null);
                    if (vaga < 0) {
                        g.cmd = '';
                        g.msgs = ['> BUS ' + bus.t + bus.cap, 'SEM VAGA LIVRE'];
                        this.bip(140, 0.25, 'sawtooth');
                        return;
                    }
                    const run = g.run;
                    g.ocupado = true;
                    if (g.t0 === null) g.t0 = performance.now();
                    await this.espera(160); if (!this.vivo(run)) return;
                    await this.estacionar(run, bus, vaga);
                },

                async embarcar(run) {
                    const g = this.g;
                    while (g.fila.length) {
                        const vaga = vagaDeEmbarque(g.vagas, g.fila[0]);
                        if (vaga < 0) break;
                        g.fila.shift();
                        g.vagas[vaga].n++;
                        this.bip(760, 0.06, 'square', 0.03);
                        await this.espera(340); if (!this.vivo(run)) return false;
                        if (g.vagas[vaga].n >= g.vagas[vaga].cap) {
                            g.msgs = ['FULL'];
                            this.bip(880, 0.2, 'triangle', 0.05);
                            await this.espera(550); if (!this.vivo(run)) return false;
                            g.vagas[vaga].x = 'dir';
                            g.msgs = ['FULL', 'DEPARTED.'];
                            this.bip(90, 0.9, 'sawtooth', 0.03);
                            await this.espera(1000); if (!this.vivo(run)) return false;
                            g.onibus[g.vagas[vaga].id].st = 'partiu';
                            g.vagas[vaga] = null;
                            await this.espera(250); if (!this.vivo(run)) return false;
                        }
                    }
                    return true;
                },

                // Fila parada: o da frente não tem ônibus estacionado com lugar e não há como trazer um.
                // Fila vazia: os ônibus que sobraram estacionados (não lotados) partem assim mesmo.
                async partirRestantes(run) {
                    const g = this.g;
                    const restantes = g.vagas.map((v, i) => (v ? i : -1)).filter((i) => i >= 0);
                    if (!restantes.length) return true;
                    g.msgs = ['QUEUE EMPTY', 'DEPARTED.'];
                    this.bip(90, 0.9, 'sawtooth', 0.03);
                    restantes.forEach((i) => { g.vagas[i].x = 'dir'; });
                    await this.espera(1000); if (!this.vivo(run)) return false;
                    restantes.forEach((i) => {
                        g.onibus[g.vagas[i].id].st = 'partiu';
                        g.vagas[i] = null;
                    });
                    await this.espera(250);
                    return this.vivo(run);
                },

                bloqueada() {
                    const g = this.g;
                    if (!g.fila.length || vagaDeEmbarque(g.vagas, g.fila[0]) >= 0) return false;
                    return g.vagas.indexOf(null) < 0 || !g.onibus.some((b) => b.st === 'livre' && b.t === g.fila[0]);
                },

                async concluir(run) {
                    const g = this.g;
                    if (!g.fila.length) {
                        if (g.tempo === null) g.tempo = Math.round(performance.now() - g.t0) + g.penalidade;
                        if (!(await this.partirRestantes(run))) return;
                        if (!(await this.digitar(run, 'CLEAR'))) return;
                        if (!(await this.apertar(run, 'ENTER', 160))) return;
                        g.cmd = '';
                        g.msgs = ['> CLEAR', 'LEVEL COMPLETE'];
                        this.bip(660, 0.15, 'triangle', 0.05);
                        await this.espera(600); if (!this.vivo(run)) return;
                        g.status = 'clear';
                        g.estrelas = estrelasDaFase(g.dicas, this.falhas);
                        if (this.diario) this.enviarDiario(g.tempo);
                        else this.enviarResultado(this.nivel + 1, g.tempo, g.estrelas);
                        this.chaveFalhas = '';
                    } else if (this.bloqueada()) {
                        g.msgs = ['QUEUE BLOCKED'];
                        this.bip(120, 0.5, 'sawtooth', 0.06);
                        await this.espera(900); if (!this.vivo(run)) return;
                        this.falhas++;
                        g.status = 'over';
                    } else {
                        g.msgs = ['READY.', 'ESCOLHA O PROXIMO ONIBUS'];
                    }
                    g.ocupado = false;
                },

                // --- apresentação ---
                estiloOnibus(vaga) {
                    const alto = this.compacto ? 48 : 80;
                    const base = 'position:absolute;left:92px;top:50%;margin-top:-' + (alto / 2) + 'px;width:21ch;height:' + alto + 'px;font-size:18px;';
                    const x = {
                        esq: 'transform:translateX(-420px);transition:none;',
                        dentro: 'transform:translateX(0);transition:transform .8s cubic-bezier(.3,.7,.2,1);',
                        dir: 'transform:translateX(420px);transition:transform .95s cubic-bezier(.6,0,.9,.5);',
                    };
                    return base + x[vaga.x];
                },

                celulas(vaga) {
                    return Array.from({ length: vaga.cap }, (_, k) => (k < vaga.n ? vaga.t : '·'));
                },

                estiloBotaoOnibus(bus) {
                    const base = this.botoesPequenos
                        ? 'min-width:54px;height:40px;padding:0 4px;font-size:28px;line-height:1;'
                        : 'min-width:62px;height:48px;padding:0 6px;font-size:32px;line-height:1;';
                    if (bus.st !== 'livre') return base + 'opacity:.3;cursor:default;';
                    const dica = this.g.dica && this.g.dica.t === bus.t && this.g.dica.cap === bus.cap ? 'border-style:solid;box-shadow:0 0 10px var(--ph);' : '';
                    return base + dica + (this.parado || this.modo === 'hard' ? 'opacity:.55;cursor:default;' : '');
                },

                estiloTecla(tecla) {
                    const larga = tecla.length > 1 && tecla !== 'SIMB';
                    const flex = tecla === ' ' ? 1 : (tecla === 'ENTER' || tecla === 'SHIFT' ? 1.7 : (tecla === 'SIMB' || tecla === 'DEL' || tecla === 'CAPS' ? 1.4 : 1));
                    const base = 'flex:' + flex + ' 1 0;min-width:0;height:clamp(20px,3.6vh,34px);cursor:pointer;box-sizing:border-box;text-align:center;display:flex;align-items:center;justify-content:center;border-radius:5px;'
                        + 'font-size:' + (larga ? 11 : 16) + 'px;letter-spacing:.04em;';
                    return base + (this.g.tecla === tecla || (tecla === 'SIMB' && this.simbolos)
                        ? 'background:#3b3a36;color:var(--ph);border-bottom:1px solid #0d0d0c;transform:translateY(2px);box-shadow:0 0 8px var(--glow);'
                        : 'background:#2a2926;color:#cfcac0;border-bottom:3px solid #0d0d0c;box-shadow:inset 0 1px 0 rgba(255,255,255,.12);');
                },
            };
        }
    </script>

    <div x-data="terminal()" :class="'t-raiz ph-' + fosforo" :style="{ '--scan': scanAlfa }" x-cloak
         style="position:fixed;top:0;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:min(100%,800px);box-sizing:border-box;display:flex;flex-direction:column;gap:8px;overflow:hidden;
                padding:max(8px, env(safe-area-inset-top)) 10px max(6px, calc(env(safe-area-inset-bottom) - 16px));">
        <template x-if="g">
            <div style="display:contents">

                {{-- Monitor --}}
                <div class="t-monitor" style="flex:1;min-height:0;box-sizing:border-box;padding:12px;border-radius:22px 22px 10px 10px;background:linear-gradient(180deg,#c4bdad,#a9a292);box-shadow:inset 0 2px 0 rgba(255,255,255,.35),0 10px 24px rgba(0,0,0,.55);display:flex">
                    <div style="flex:1;min-height:0;box-sizing:border-box;padding:8px;border-radius:18px;background:#161614;box-shadow:inset 0 0 14px #000;display:flex">
                        <div style="flex:1;min-height:0;position:relative;overflow:hidden;border-radius:30px / 26px;background:var(--bg);color:var(--ph);box-shadow:inset 0 0 40px rgba(0,0,0,.7)">

                            <div class="crt-flick t-tela" style="position:absolute;inset:0;box-sizing:border-box;padding:14px 14px 10px;display:flex;flex-direction:column;gap:8px;text-shadow:0 0 5px var(--glow)">

                                {{-- Cabeçalho --}}
                                <div class="t-cab" style="display:flex;justify-content:space-between;align-items:flex-start">
                                    <div>
                                        <div style="font-size:32px;letter-spacing:.38em;line-height:30px">TERMINAL</div>
                                        <div style="font-size:20px;line-height:22px;color:var(--ph-dim)" x-text="tituloNivel + (modo === 'hard' ? ' · HARD' : '')"></div>
                                    </div>
                                    <div style="text-align:right;font-size:20px;line-height:22px">
                                        <div x-text="'VAGAS: ' + g.vagas.length"></div>
                                        <div x-text="'ONIBUS: ' + g.onibus.length"></div>
                                    </div>
                                </div>
                                <div class="t-cab" style="border-top:1px dashed var(--ph-dim)"></div>

                                {{-- Passageiros --}}
                                <div class="t-pass">
                                    <div style="font-size:18px;letter-spacing:.1em;color:var(--ph-dim)">PASSAGEIROS</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:0 4px;margin-top:2px" :style="{ minHeight: (g.fila.length > 12 ? 24 : 46) + 'px' }">
                                        <template x-for="(p, i) in g.fila" :key="i + p">
                                            <div style="width:22px;text-align:center;font-size:22px;line-height:24px">
                                                <div x-text="p" :style="g.fila.length > 12 && i === 0 ? 'background:var(--ph);color:var(--bg);text-shadow:none' : ''"></div>
                                                <div style="font-size:18px;line-height:16px" x-show="g.fila.length <= 12" x-text="i === 0 ? '^' : ' '"></div>
                                            </div>
                                        </template>
                                        <div x-show="g.fila.length === 0" style="font-size:20px;line-height:24px;color:var(--ph-dim)">-- FILA VAZIA --</div>
                                    </div>
                                </div>

                                {{-- Ônibus disponíveis --}}
                                <div class="t-onib">
                                    <div style="font-size:18px;letter-spacing:.1em;color:var(--ph-dim)">ONIBUS DISPONIVEIS</div>
                                    <div style="display:flex;flex-wrap:wrap;margin-top:4px" :style="{ gap: (botoesPequenos ? 6 : 8) + 'px' }">
                                        <template x-for="b in g.onibus" :key="b.id">
                                            <button type="button" class="t-btn t-bus-btn"
                                                    :aria-label="b.st === 'livre' ? 'Colocar ônibus ' + b.t + ' de ' + b.cap + ' lugares em uma vaga' : 'Ônibus ' + b.t + ' de ' + b.cap + ' lugares já em uso'"
                                                    :disabled="b.st !== 'livre' || parado || modo === 'hard'"
                                                    @click="escolher(b.id)"
                                                    :style="estiloBotaoOnibus(b)"
                                                    ><span x-text="b.st === 'livre' ? b.t : ' '"></span><span style="font-size:20px;vertical-align:-3px" x-text="b.st === 'livre' ? b.cap : ' '"></span></button>
                                        </template>
                                    </div>
                                </div>

                                {{-- Vagas empilhadas --}}
                                <div x-ref="vagas" class="t-vagas" style="flex:1;min-height:0;display:flex;flex-direction:column;gap:6px">
                                    <template x-for="(v, i) in g.vagas" :key="i">
                                        <div data-vaga style="flex:1;min-height:0;position:relative;overflow:hidden;border:1px dashed var(--ph)">
                                            <div style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:18px;line-height:17px">
                                                <div style="letter-spacing:.06em;white-space:nowrap" x-text="fino ? 'V' + (i + 1) + ' ' + (v ? v.t + v.n + '/' + v.cap : 'LIVRE') : 'VAGA ' + (i + 1)"></div>
                                                <div x-show="!fino" style="color:var(--ph-dim)" x-text="v ? 'BUS ' + v.t + ' ' + v.n + '/' + v.cap : 'LIVRE'"></div>
                                            </div>
                                            <div x-show="v" :style="v ? estiloOnibus(v) : ''">
                                                <div :style="'width:21ch;height:100%;transform:scale(' + escala + ');transform-origin:0 50%'">
                                                <pre class="bus-art" x-text="compacto ? arteCompacta : arteGrande"></pre>
                                                <div :style="'position:absolute;left:1ch;top:16px;width:17ch;display:flex;justify-content:center;align-items:center;font-size:18px;height:' + (compacto ? 16 : 32) + 'px'">
                                                    <template x-for="(c, k) in (v ? celulas(v) : [])" :key="k">
                                                        <span style="width:4ch;text-align:center" x-text="c"></span>
                                                    </template>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Console --}}
                                <div class="t-cons" style="border-top:1px dashed var(--ph-dim);padding-top:6px;font-size:20px;line-height:20px">
                                    <div style="min-height:40px;color:var(--ph-dim)">
                                        <template x-for="(m, i) in g.msgs" :key="i + m">
                                            <div x-text="m"></div>
                                        </template>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:2px;gap:6px">
                                        <div style="display:flex;align-items:center;white-space:nowrap">&gt;&nbsp;<span x-text="g.cmd"></span><span class="crt-blink" style="display:inline-block;width:9px;height:15px;margin-left:3px;background:var(--ph)"></span></div>
                                        <div style="display:flex;gap:6px">
                                            <button type="button" class="t-btn" @click="abrirMenu()" aria-label="Voltar à tela inicial"
                                                    style="height:28px;padding:0 8px;font-size:18px;letter-spacing:.08em">MENU</button>
                                            <button type="button" class="t-btn" @click="pedirDica()" aria-label="Pedir uma dica (custa 10 segundos e uma estrela)"
                                                    style="height:28px;padding:0 8px;font-size:18px;letter-spacing:.08em">DICA</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Bloqueio --}}
                            <div x-show="g.status === 'over'" style="position:absolute;inset:0;background:var(--veil);text-shadow:0 0 8px var(--glow)">
                                <div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;text-align:center">
                                    <div style="font-size:40px;line-height:40px;letter-spacing:.1em">QUEUE BLOCKED</div>
                                    <div style="font-size:24px;color:var(--ph-dim)">GAME OVER</div>
                                    <div style="max-width:240px;font-size:19px;line-height:20px;color:var(--ph-dim)">Nenhum ônibus compatível estacionado e nenhuma vaga ou ônibus livre para o primeiro da fila.</div>
                                    <button type="button" class="t-btn" @click="reiniciar(nivel)" style="margin-top:10px;height:48px;padding:0 24px;font-size:26px;letter-spacing:.1em">&gt; REBOOT</button>
                                </div>
                            </div>

                            {{-- Nível concluído --}}
                            <div x-show="g.status === 'clear'" style="position:absolute;inset:0;background:var(--veil);text-shadow:0 0 8px var(--glow)">
                                <div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;text-align:center">
                                    <div style="font-size:48px;line-height:48px;letter-spacing:.14em">CLEAR</div>
                                    <div style="font-size:24px;color:var(--ph-dim)" x-text="diario ? 'DESAFIO DIARIO CONCLUIDO' : 'NIVEL ' + numeroNivel + ' CONCLUIDO'"></div>
                                    <div x-show="!diario" style="font-size:34px;line-height:30px;letter-spacing:.2em" x-text="'★'.repeat(g.estrelas) + '☆'.repeat(3 - g.estrelas)"></div>
                                    <div style="font-size:24px" x-text="'TEMPO ' + formatarTempo(g.tempo || 0)"></div>
                                    <div x-show="g.penalidade > 0" style="font-size:18px;color:var(--ph-dim)" x-text="'(INCLUI +' + (g.penalidade / 1000) + 'S DE DICAS)'"></div>
                                    <div x-show="recorde" class="crt-blink" style="font-size:20px;letter-spacing:.1em">* NOVO RECORDE *</div>
                                    <template x-for="n in g.novas" :key="n">
                                        <div style="font-size:19px;letter-spacing:.06em" x-text="'ACHIEVEMENT UNLOCKED: ' + n"></div>
                                    </template>
                                    <div style="max-width:240px;font-size:19px;line-height:20px;color:var(--ph-dim)">Todos os passageiros foram transportados.</div>
                                    <button type="button" class="t-btn" x-show="!diario" @click="reiniciar(nivel + 1)" style="margin-top:10px;height:48px;padding:0 24px;font-size:26px;letter-spacing:.1em">&gt; RUN</button>
                                    <button type="button" class="t-btn" x-show="diario" @click="abrirMenu()" style="margin-top:10px;height:48px;padding:0 24px;font-size:26px;letter-spacing:.1em">&gt; MENU</button>
                                </div>
                            </div>

                            {{-- Tela inicial --}}
                            <div class="t-scr" x-show="tela === 'home'" style="position:absolute;inset:0;box-sizing:border-box;padding:14px;background:var(--bg);text-shadow:0 0 5px var(--glow);justify-content:center;gap:14px">
                                <div style="text-align:center;margin-bottom:6px">
                                    <div style="font-size:40px;letter-spacing:.38em;line-height:38px;margin-right:-.38em">TERMINAL</div>
                                    <div style="font-size:18px;line-height:20px;color:var(--ph-dim);letter-spacing:.08em">SISTEMA DE EMBARQUE DE ONIBUS</div>
                                    <div style="font-size:22px;line-height:26px;margin-top:2px;letter-spacing:.3em">● ■ ▲ ◆</div>
                                </div>
                                <button type="button" class="t-btn" @click="jogar()" aria-label="Jogar"
                                        style="height:64px;font-size:40px;letter-spacing:.2em;line-height:1">
                                    &gt; RUN <span style="font-size:20px;letter-spacing:.08em;vertical-align:4px" x-text="'NIVEL ' + numeroNivel + (modo === 'hard' ? ' · HARD' : '')"></span>
                                </button>
                                <button type="button" class="t-btn" @click="jogarDiario()" aria-label="Desafio diário"
                                        style="height:44px;font-size:26px;letter-spacing:.1em;line-height:1"
                                        x-text="'DESAFIO DIARIO' + (diarioInfo && diarioInfo.sequencia ? ' · ' + diarioInfo.sequencia + 'D' : '')"></button>
                                <button type="button" class="t-btn" @click="tela = 'config'" aria-label="Configurações" style="height:44px;font-size:26px;letter-spacing:.14em;line-height:1">CONFIGURACOES</button>
                                <button type="button" class="t-btn" @click="abrirRank()" aria-label="Rankings" style="height:44px;font-size:26px;letter-spacing:.14em;line-height:1">RANK</button>
                            </div>

                            {{-- Configurações --}}
                            <div class="t-scr" x-show="tela === 'config'" style="position:absolute;inset:0;box-sizing:border-box;padding:14px;background:var(--bg);text-shadow:0 0 5px var(--glow);gap:10px;overflow-y:auto">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                                    <div style="font-size:24px;letter-spacing:.06em;line-height:30px">CONFIGURACOES</div>
                                    <button type="button" class="t-btn" @click="tela = 'home'" style="height:30px;padding:0 10px;font-size:20px;letter-spacing:.04em;white-space:nowrap;flex:none">&lt; VOLTAR</button>
                                </div>
                                <div>
                                    <div style="font-size:18px;letter-spacing:.1em;color:var(--ph-dim);margin-bottom:4px">MODO</div>
                                    <div style="display:flex;gap:6px">
                                        <template x-for="m in [['normal', 'NORMAL'], ['hard', 'HARD']]" :key="m[0]">
                                            <button type="button" class="t-btn" @click="trocarModo(m[0])" :aria-pressed="modo === m[0]"
                                                    style="flex:1;height:32px;font-size:22px;letter-spacing:.12em"
                                                    :style="modo === m[0] ? { background: 'var(--ph)', color: 'var(--bg)', textShadow: 'none' } : {}" x-text="m[1]"></button>
                                        </template>
                                    </div>
                                    <div style="font-size:17px;line-height:18px;margin-top:3px;color:var(--ph-dim)" x-text="modo === 'hard' ? 'SOMENTE TECLADO: DIGITE OS COMANDOS' : 'MOUSE OU TOQUE (O TECLADO TAMBEM VALE)'"></div>
                                </div>
                                <div>
                                    <div style="font-size:18px;letter-spacing:.1em;color:var(--ph-dim);margin-bottom:4px">FASES</div>
                                    <div style="display:flex;gap:6px">
                                        <button type="button" class="t-btn" @click="tela = 'fases'" aria-label="Escolher fase"
                                                style="flex:1;height:32px;padding:0 6px;font-size:20px;letter-spacing:.04em;white-space:nowrap">ESCOLHER FASE</button>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:18px;letter-spacing:.1em;color:var(--ph-dim);margin-bottom:4px">TELA E SOM</div>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                                        <button type="button" class="t-btn" @click="trocarFosforo()" aria-label="Trocar a cor do fósforo da tela"
                                                style="height:32px;padding:0 10px;font-size:20px;letter-spacing:.06em" x-text="'COR: ' + nomeFosforo"></button>
                                        <button type="button" class="t-btn" @click="alternarSom()" :aria-label="mudo ? 'Ligar o som' : 'Desligar o som'"
                                                style="height:32px;padding:0 10px;font-size:20px;letter-spacing:.06em" x-text="'SOM: ' + (mudo ? 'OFF' : 'ON')"></button>
                                        <button type="button" class="t-btn" @click="trocarScan()" aria-label="Trocar a intensidade das scanlines"
                                                style="height:32px;padding:0 10px;font-size:20px;letter-spacing:.06em" x-text="'SCAN: ' + nomeScan"></button>
                                        <button type="button" class="t-btn" @click="alternarBoot()" aria-label="Ligar ou desligar a animação de boot"
                                                style="height:32px;padding:0 10px;font-size:20px;letter-spacing:.06em" x-text="'BOOT: ' + (boot ? 'ON' : 'OFF')"></button>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:18px;letter-spacing:.1em;color:var(--ph-dim);margin-bottom:4px">PROGRESSO</div>
                                    <button type="button" class="t-btn" @click="rebootGeral()" aria-label="Reiniciar o progresso e voltar ao nível 1"
                                            style="height:32px;padding:0 10px;font-size:20px;letter-spacing:.06em" x-text="confirmaReboot ? 'CONFIRMA? NIVEL 01' : 'REBOOT'"></button>
                                </div>
                            </div>

                            {{-- Rankings --}}
                            <div class="t-scr" x-show="tela === 'rank'" style="position:absolute;inset:0;box-sizing:border-box;padding:14px;background:var(--bg);text-shadow:0 0 5px var(--glow);gap:8px">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                                    <div style="font-size:30px;letter-spacing:.2em;line-height:30px">RANK</div>
                                    <button type="button" class="t-btn" @click="tela = 'home'" style="height:30px;padding:0 10px;font-size:20px;letter-spacing:.04em;white-space:nowrap;flex:none">&lt; VOLTAR</button>
                                </div>
                                <div style="flex:1;min-height:0;display:flex;flex-direction:column">
                                    <div style="border:1px dashed var(--ph);padding:8px 10px;flex:1;min-height:0;display:flex;flex-direction:column;gap:6px">
                                        <div style="font-size:22px;letter-spacing:.12em;line-height:22px">★ FUNCIONARIO DO MES</div>
                                        <div style="display:flex;gap:6px">
                                            <template x-for="a in [['fase', 'POR FASE'], ['geral', 'GERAL'], ['fases', 'FASES'], ['diario', 'DIARIO']]" :key="a[0]">
                                                <button type="button" class="t-btn" @click="abaRank = a[0]"
                                                        style="height:26px;padding:0 5px;font-size:18px;letter-spacing:.02em;white-space:nowrap"
                                                        :style="abaRank === a[0] ? { background: 'var(--ph)', color: 'var(--bg)', textShadow: 'none' } : {}" x-text="a[1]"></button>
                                            </template>
                                        </div>
                                        <div style="font-size:17px;line-height:18px;color:var(--ph-dim)"
                                             x-text="({ fase: 'MENOR TEMPO NA FASE', geral: 'MENOR TEMPO MEDIO POR FASE', fases: 'QUEM CONCLUIU MAIS FASES', diario: 'MENOR TEMPO NO DESAFIO DE HOJE' }[abaRank]) + ' · ' + modo.toUpperCase()"></div>
                                        <div x-show="abaRank === 'fase'" style="display:flex;align-items:center;gap:8px;font-size:20px">
                                            <button type="button" class="t-btn" @click="trocarFaseRank(-1)" aria-label="Fase anterior" style="width:30px;height:26px;font-size:20px;line-height:1">&lt;</button>
                                            <span x-text="'FASE ' + String(faseRank).padStart(2, '0')"></span>
                                            <button type="button" class="t-btn" @click="trocarFaseRank(1)" aria-label="Próxima fase" style="width:30px;height:26px;font-size:20px;line-height:1">&gt;</button>
                                        </div>
                                        <div style="flex:1;min-height:0;overflow-y:auto;font-size:20px;line-height:22px">
                                            <template x-for="(l, i) in linhasRank" :key="abaRank + i">
                                                <div style="display:flex;gap:8px;padding:0 4px"
                                                     :style="l.eu ? { background: 'var(--ph)', color: 'var(--bg)', textShadow: 'none' } : {}">
                                                    <span style="width:2ch" x-text="String(i + 1).padStart(2, '0')"></span>
                                                    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="(i === 0 ? '★ ' : '') + l.nome"></span>
                                                    <span x-show="abaRank === 'geral' || abaRank === 'fases'" style="color:inherit;opacity:.75" x-text="l.fases + (l.fases === 1 ? ' FASE' : ' FASES')"></span>
                                                    <span style="min-width:7ch;text-align:right" x-text="formatarTempo(l.tempo_ms)"></span>
                                                </div>
                                            </template>
                                            <div x-show="rankPronto && linhasRank.length === 0" style="color:var(--ph-dim)">-- SEM REGISTROS --</div>
                                            <div x-show="!rankPronto && !rankErro" style="color:var(--ph-dim)">CARREGANDO...</div>
                                            <div x-show="rankErro" style="color:var(--ph-dim)">-- RANKING OFFLINE --</div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="t-btn" @click="tela = 'conquistas'" aria-label="Conquistas"
                                        style="height:34px;font-size:22px;letter-spacing:.1em" x-text="'CONQUISTAS ' + conquistas.length + '/' + catalogo.length"></button>
                            </div>

                            {{-- Seleção de fases --}}
                            <div class="t-scr" x-show="tela === 'fases'" style="position:absolute;inset:0;box-sizing:border-box;padding:14px;background:var(--bg);text-shadow:0 0 5px var(--glow);gap:8px">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <div style="font-size:30px;letter-spacing:.2em;line-height:30px">FASES</div>
                                    <button type="button" class="t-btn" @click="tela = 'config'" style="height:30px;padding:0 10px;font-size:20px;letter-spacing:.04em;white-space:nowrap;flex:none">&lt; VOLTAR</button>
                                </div>
                                <div style="font-size:18px;color:var(--ph-dim)" x-text="modo.toUpperCase() + ' · ' + totalEstrelas + ' ESTRELAS · TOQUE PARA JOGAR'"></div>
                                <div style="flex:1;min-height:0;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:6px;align-content:start">
                                    <template x-for="f in fasesLiberadas" :key="f">
                                        <button type="button" class="t-btn" @click="escolherFase(f)" :aria-label="'Jogar a fase ' + f"
                                                style="height:56px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;line-height:1">
                                            <span style="font-size:26px" x-text="String(f).padStart(2, '0')"></span>
                                            <span style="font-size:18px;letter-spacing:.1em" x-text="estrelasMapa[f] ? '★'.repeat(estrelasMapa[f]) + '☆'.repeat(3 - estrelasMapa[f]) : (f - 1 === nivel ? 'NOVA' : '---')"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Conquistas --}}
                            <div class="t-scr" x-show="tela === 'conquistas'" style="position:absolute;inset:0;box-sizing:border-box;padding:14px;background:var(--bg);text-shadow:0 0 5px var(--glow);gap:8px">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <div style="font-size:28px;letter-spacing:.1em;line-height:30px">CONQUISTAS</div>
                                    <button type="button" class="t-btn" @click="tela = 'rank'" style="height:30px;padding:0 10px;font-size:20px;letter-spacing:.04em;white-space:nowrap;flex:none">&lt; VOLTAR</button>
                                </div>
                                <div style="font-size:18px;color:var(--ph-dim)" x-text="conquistas.length + ' DE ' + catalogo.length + ' DESBLOQUEADAS'"></div>
                                <div style="flex:1;min-height:0;overflow-y:auto;font-size:20px;line-height:22px;display:flex;flex-direction:column;gap:6px">
                                    <template x-for="c in catalogo" :key="c.c">
                                        <div style="display:flex;gap:8px;border-bottom:1px dashed var(--ph-dim);padding-bottom:4px" :style="{ opacity: conquistas.includes(c.c) ? 1 : 0.55 }">
                                            <span style="width:3ch" x-text="conquistas.includes(c.c) ? '[X]' : '[ ]'"></span>
                                            <span style="flex:1;min-width:0">
                                                <span style="display:block" x-text="c.secreta && !conquistas.includes(c.c) ? '???' : c.nome"></span>
                                                <span style="display:block;font-size:17px;color:var(--ph-dim)" x-text="c.secreta && !conquistas.includes(c.c) ? 'SECRETA: TENTE ALGO NO CONSOLE' : c.desc"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Boot de BIOS --}}
                            <div class="t-scr" x-show="tela === 'boot'" @pointerdown="pularBoot()" style="position:absolute;inset:0;box-sizing:border-box;padding:18px 16px;background:var(--bg);text-shadow:0 0 5px var(--glow);justify-content:space-between;cursor:pointer">
                                <div style="font-size:21px;line-height:23px">
                                    <template x-for="(l, i) in bootLinhas" :key="i">
                                        <div style="white-space:pre;min-height:23px" x-text="l"></div>
                                    </template>
                                    <span class="crt-blink" style="display:inline-block;width:9px;height:15px;background:var(--ph)"></span>
                                </div>
                                <div style="font-size:17px;color:var(--ph-dim);text-align:center;letter-spacing:.1em">TOQUE OU TECLE PARA PULAR</div>
                            </div>

                            {{-- Scanlines e vinheta --}}
                            <div aria-hidden="true" style="position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,rgba(0,0,0,var(--scan,.24)) 0,rgba(0,0,0,var(--scan,.24)) 1px,transparent 1px,transparent 3px)"></div>
                            <div aria-hidden="true" style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 45%,transparent 58%,rgba(0,0,0,.55) 100%)"></div>
                        </div>
                    </div>
                </div>

                {{-- Teclado --}}
                <div class="t-teclado" style="box-sizing:border-box;padding:9px 10px 10px;border-radius:10px 10px 18px 18px;background:linear-gradient(180deg,#a9a292,#8f897a);box-shadow:inset 0 2px 0 rgba(255,255,255,.25),0 6px 14px rgba(0,0,0,.5)">
                    <div style="display:flex;flex-direction:column;gap:5px">
                        <template x-for="(linha, li) in teclado" :key="li">
                            <div :style="'display:flex;gap:4px;padding:0 ' + linha.pad + 'px'">
                                {{-- Com o botão de símbolo ativo, a fileira de números vira a fileira de símbolos do nível. --}}
                                <template x-if="li === 0 && simbolos">
                                    <div style="display:contents">
                                        <template x-for="sim in simbolosNivel" :key="sim">
                                            <div role="button" :aria-label="'Inserir símbolo ' + sim" :style="estiloTecla(sim) + 'font-size:20px;'" @pointerdown.prevent="inserirSimbolo(sim)" x-text="sim"></div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!(li === 0 && simbolos)">
                                    <div style="display:contents">
                                        <template x-for="t in linha.teclas" :key="t">
                                            <div role="button" :aria-label="t === 'SIMB' ? 'Símbolos' : (t === ' ' ? 'Espaço' : t)" :style="estiloTecla(t)" @pointerdown.prevent="teclar(t)"
                                                 x-text="t === ' ' ? '' : (t === 'SIMB' ? '●■▲' : t)"></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-Terminal::layout>
