<x-Engenhocopolis::layout>
    {{-- Lógica pura do jogo (fases, simulação, avisos de conexão). Sem DOM: é carregada também pelo verificador em Node (ferramentas/verificar-fases.mjs). --}}
    @verbatim
    <script>
        // ENGENHO-LOGICA-INICIO
        // Direções da grade lógica: 0 = +x, 1 = +y, 2 = -x, 3 = -y. Na tela isométrica, +x desce para a direita e +y desce para a esquerda.
        const DIRS = [[1, 0], [0, 1], [-1, 0], [0, -1]];
        const TAMANHOS = { 1: 'pequeno', 2: 'médio', 3: 'grande' };
        const LETRAS = { 1: 'P', 2: 'M', 3: 'G' };
        const NOMES_PECA = { reta: 'Esteira reta', curva: 'Esteira curva', separador: 'Separador com vão', desviador: 'Desviador' };
        const NA_PECA = { reta: 'na esteira reta', curva: 'na esteira curva', separador: 'no separador', desviador: 'no desviador' };
        const TIPOS_PECA = ['reta', 'curva', 'separador', 'desviador'];
        const ESPELHAVEIS = ['curva', 'desviador'];
        const LIMITE_TICKS = 400;
        const ESPACO_PADRAO = 2;

        // A cor do pacote não é atributo: o renderizador pinta cada pacote com a cor do coletor de destino.
        function pac(tamanho, destino) {
            return { largura: { P: 1, M: 2, G: 3 }[tamanho], destino };
        }

        function peca(tipo, x, y, dir, extra) {
            return Object.assign({ tipo, x, y, dir }, extra || {});
        }

        // Setores (distritos) de Engenhocópolis; cada fase aponta para um deles.
        const SETORES = ['Pátio das Esteiras', 'Galpão do Vão', 'Ala das Medidas', 'Entroncamento'];

        const FASES = [
            {
                nome: 'Primeiro movimento', setor: 0,
                objetivo: 'Feche a lacuna da esteira até o coletor A.',
                intro: 'Toque na esteira reta do inventário e depois na célula vazia da linha. Quando terminar, aperte EXECUTAR.',
                largura: 5, altura: 3,
                entrada: { x: 0, y: 1, dir: 0 },
                coletores: [{ id: 'A', x: 4, y: 1, tipo: 'final' }],
                obstaculos: [[1, 2], [3, 0]],
                fixas: [peca('reta', 1, 1, 0), peca('reta', 3, 1, 0)],
                pacotes: [pac('M', 'A'), pac('M', 'A')],
                estoque: { reta: 1 },
                referencia: [peca('reta', 2, 1, 0)],
            },
            {
                nome: 'Virando a esquina', setor: 0,
                objetivo: 'Leve os pacotes até o coletor A, que fica do outro lado.',
                intro: 'A esteira curva muda o sentido do pacote. Use GIRAR e ESPELHAR até a seta seguir o caminho.',
                largura: 4, altura: 4,
                entrada: { x: 0, y: 0, dir: 0 },
                coletores: [{ id: 'A', x: 2, y: 3, tipo: 'final' }],
                obstaculos: [[0, 3], [3, 3]],
                fixas: [peca('reta', 1, 0, 0)],
                pacotes: [pac('P', 'A'), pac('G', 'A'), pac('M', 'A')],
                estoque: { reta: 2, curva: 1 },
                referencia: [peca('curva', 2, 0, 1, { lado: 'D' }), peca('reta', 2, 1, 1), peca('reta', 2, 2, 1)],
            },
            {
                nome: 'Ligar os pontos', setor: 0,
                objetivo: 'Monte um caminho entre a entrada e o coletor A, desviando das caixas.',
                intro: 'Peças erradas aparecem com um alerta vermelho antes mesmo de executar. Arraste uma peça instalada para mudá-la de lugar.',
                largura: 6, altura: 5,
                entrada: { x: 0, y: 4, dir: 0 },
                coletores: [{ id: 'A', x: 5, y: 1, tipo: 'final' }],
                obstaculos: [[3, 4], [4, 4], [5, 4], [3, 3], [4, 3], [3, 2], [4, 2]],
                fixas: [],
                pacotes: [pac('M', 'A'), pac('P', 'A'), pac('G', 'A')],
                estoque: { reta: 6, curva: 2 },
                referencia: [
                    peca('reta', 1, 4, 0), peca('curva', 2, 4, 3, { lado: 'E' }), peca('reta', 2, 3, 3), peca('reta', 2, 2, 3),
                    peca('curva', 2, 1, 0, { lado: 'D' }), peca('reta', 3, 1, 0), peca('reta', 4, 1, 0),
                ],
            },
            {
                nome: 'O vão', setor: 1,
                objetivo: 'Pequenos caem no fosso B; grandes seguem até o coletor A.',
                intro: 'O separador tem um vão no meio: pacotes com largura MENOR que a abertura caem no coletor de baixo. Instale-o sobre o fosso B e ajuste a abertura.',
                largura: 6, altura: 3,
                entrada: { x: 0, y: 1, dir: 0 },
                coletores: [{ id: 'A', x: 5, y: 1, tipo: 'final' }, { id: 'B', x: 3, y: 1, tipo: 'fosso' }],
                obstaculos: [[0, 0], [5, 2]],
                fixas: [peca('reta', 1, 1, 0), peca('reta', 2, 1, 0), peca('reta', 4, 1, 0)],
                pacotes: [pac('P', 'B'), pac('G', 'A'), pac('P', 'B'), pac('G', 'A')],
                estoque: { separador: 1 },
                limites: { abertura: [1, 4] },
                referencia: [peca('separador', 3, 1, 0, { abertura: 2 })],
            },
            {
                nome: 'Passa ou cai', setor: 1,
                objetivo: 'Pequenos no fosso B, grandes no coletor A.',
                intro: 'Agora o fosso não está na linha. Monte o caminho e coloque o separador exatamente sobre ele.',
                largura: 4, altura: 4,
                entrada: { x: 0, y: 0, dir: 0 },
                coletores: [{ id: 'A', x: 2, y: 3, tipo: 'final' }, { id: 'B', x: 2, y: 1, tipo: 'fosso' }],
                obstaculos: [[0, 2], [0, 3], [3, 3]],
                fixas: [peca('reta', 1, 0, 0)],
                pacotes: [pac('G', 'A'), pac('P', 'B'), pac('P', 'B'), pac('G', 'A')],
                estoque: { reta: 1, curva: 1, separador: 1 },
                limites: { abertura: [1, 4] },
                referencia: [peca('curva', 2, 0, 1, { lado: 'D' }), peca('separador', 2, 1, 1, { abertura: 2 }), peca('reta', 2, 2, 1)],
            },
            {
                nome: 'Medida certa', setor: 1,
                objetivo: 'Pequenos e médios no fosso B; só os grandes chegam ao coletor A.',
                intro: 'Atenção ao limite: largura IGUAL à abertura continua apoiada nas bordas. P = 1, M = 2, G = 3.',
                largura: 6, altura: 3,
                entrada: { x: 5, y: 1, dir: 2 },
                coletores: [{ id: 'A', x: 0, y: 1, tipo: 'final' }, { id: 'B', x: 2, y: 1, tipo: 'fosso' }],
                obstaculos: [[0, 0], [5, 2]],
                fixas: [peca('reta', 4, 1, 2), peca('reta', 1, 1, 2)],
                pacotes: [pac('M', 'B'), pac('G', 'A'), pac('P', 'B'), pac('M', 'B'), pac('G', 'A')],
                estoque: { reta: 1, separador: 1 },
                limites: { abertura: [1, 4] },
                referencia: [peca('reta', 3, 1, 2), peca('separador', 2, 1, 2, { abertura: 3 })],
            },
            {
                nome: 'Três tamanhos', setor: 2,
                objetivo: 'Pequenos no fosso B, médios no fosso C, grandes no coletor A.',
                intro: 'Dois separadores em sequência dividem três tamanhos. Cada fosso aceita só um deles.',
                largura: 8, altura: 3,
                entrada: { x: 0, y: 1, dir: 0 },
                coletores: [{ id: 'A', x: 7, y: 1, tipo: 'final' }, { id: 'B', x: 2, y: 1, tipo: 'fosso' }, { id: 'C', x: 4, y: 1, tipo: 'fosso' }],
                obstaculos: [[0, 0], [6, 2], [7, 2]],
                fixas: [peca('reta', 1, 1, 0), peca('reta', 3, 1, 0), peca('reta', 5, 1, 0), peca('reta', 6, 1, 0)],
                pacotes: [pac('G', 'A'), pac('P', 'B'), pac('M', 'C'), pac('P', 'B'), pac('G', 'A'), pac('M', 'C')],
                estoque: { separador: 2 },
                limites: { abertura: [1, 4] },
                referencia: [peca('separador', 2, 1, 0, { abertura: 2 }), peca('separador', 4, 1, 0, { abertura: 3 })],
            },
            {
                nome: 'Volta no galpão', setor: 2,
                objetivo: 'Passe pelos dois fossos antes de chegar ao coletor A.',
                intro: 'O caminho dá a volta: use as curvas para visitar o fosso B e depois o fosso C.',
                largura: 5, altura: 5,
                entrada: { x: 0, y: 0, dir: 0 },
                coletores: [{ id: 'A', x: 2, y: 4, tipo: 'final' }, { id: 'B', x: 3, y: 0, tipo: 'fosso' }, { id: 'C', x: 3, y: 2, tipo: 'fosso' }],
                obstaculos: [[0, 2], [0, 3], [0, 4], [1, 4], [4, 4]],
                fixas: [peca('reta', 1, 0, 0), peca('reta', 2, 0, 0)],
                pacotes: [pac('P', 'B'), pac('M', 'C'), pac('G', 'A'), pac('M', 'C'), pac('P', 'B'), pac('G', 'A')],
                estoque: { separador: 2, curva: 3, reta: 2 },
                limites: { abertura: [1, 4] },
                referencia: [
                    peca('separador', 3, 0, 0, { abertura: 2 }), peca('curva', 4, 0, 1, { lado: 'D' }), peca('reta', 4, 1, 1),
                    peca('curva', 4, 2, 2, { lado: 'D' }), peca('separador', 3, 2, 2, { abertura: 3 }), peca('curva', 2, 2, 1, { lado: 'E' }),
                    peca('reta', 2, 3, 1),
                ],
            },
            {
                nome: 'Ordem dos fatores', setor: 2,
                objetivo: 'Pequenos no fosso B, médios no fosso C, grandes no coletor A. Descubra a ordem certa.',
                intro: 'O fosso C está logo à frente, mas será que ele deve vir primeiro? Pense em qual separador consegue tirar só um tamanho.',
                largura: 5, altura: 5,
                entrada: { x: 0, y: 1, dir: 0 },
                coletores: [{ id: 'A', x: 4, y: 0, tipo: 'final' }, { id: 'B', x: 1, y: 3, tipo: 'fosso' }, { id: 'C', x: 2, y: 1, tipo: 'fosso' }],
                obstaculos: [[3, 2], [4, 3], [4, 4], [0, 3], [0, 4]],
                fixas: [],
                pacotes: [pac('M', 'C'), pac('P', 'B'), pac('G', 'A'), pac('P', 'B'), pac('M', 'C'), pac('G', 'A')],
                estoque: { separador: 2, curva: 4, reta: 5 },
                limites: { abertura: [1, 4] },
                referencia: [
                    peca('curva', 1, 1, 1, { lado: 'D' }), peca('reta', 1, 2, 1), peca('separador', 1, 3, 1, { abertura: 2 }),
                    peca('curva', 1, 4, 0, { lado: 'E' }), peca('curva', 2, 4, 3, { lado: 'E' }), peca('reta', 2, 3, 3), peca('reta', 2, 2, 3),
                    peca('separador', 2, 1, 3, { abertura: 3 }), peca('curva', 2, 0, 0, { lado: 'D' }), peca('reta', 3, 0, 0),
                ],
            },
            {
                nome: 'Entroncamento', setor: 3,
                objetivo: 'Leve cada pacote ao coletor indicado na fila: A ou B.',
                intro: 'O desviador não mede nada: ele conta. Manda um grupo de pacotes para a frente e o grupo seguinte para o lado, alternando. Ajuste o tamanho do grupo e o lado que começa.',
                largura: 6, altura: 5,
                entrada: { x: 0, y: 2, dir: 0 },
                coletores: [{ id: 'A', x: 5, y: 2, tipo: 'final' }, { id: 'B', x: 2, y: 0, tipo: 'final' }],
                obstaculos: [[0, 0], [4, 4], [5, 4], [5, 0]],
                fixas: [peca('reta', 1, 2, 0), peca('reta', 3, 2, 0), peca('reta', 4, 2, 0)],
                pacotes: [
                    pac('M', 'A'), pac('P', 'A'), pac('G', 'B'), pac('M', 'B'),
                    pac('P', 'A'), pac('G', 'A'), pac('M', 'B'), pac('P', 'B'),
                ],
                estoque: { desviador: 1, reta: 1 },
                limites: { grupo: [1, 3] },
                referencia: [peca('desviador', 2, 2, 0, { lado: 'E', primeiro: 'frente', grupo: 2 }), peca('reta', 2, 1, 3)],
            },
        ];

        function chave(x, y) { return x + ',' + y; }
        function dentro(f, x, y) { return x >= 0 && y >= 0 && x < f.largura && y < f.altura; }
        function lateral(dir, lado) { return lado === 'E' ? (dir + 3) % 4 : (dir + 1) % 4; }
        function coletorEm(f, x, y) { return f.coletores.find(c => c.x === x && c.y === y) || null; }
        function obstaculoEm(f, x, y) { return (f.obstaculos || []).some(o => o[0] === x && o[1] === y); }
        function ehEntrada(f, x, y) { return f.entrada.x === x && f.entrada.y === y; }

        /** Direção de movimento com que o pacote precisa chegar à peça. */
        function direcaoDeEntrada(p) {
            if (p.tipo === 'curva') return p.lado === 'E' ? (p.dir + 1) % 4 : (p.dir + 3) % 4;
            return p.dir;
        }

        function saidasDaPeca(p) {
            return p.tipo === 'desviador' ? [p.dir, lateral(p.dir, p.lado)] : [p.dir];
        }

        /** Saída usada pelo n-ésimo pacote (0, 1, 2...) que passa pelo desviador. */
        function saidaDoDesviador(p, n) {
            const bloco = Math.floor(n / (p.grupo || 1));
            return (bloco % 2 === 0) === (p.primeiro !== 'lado') ? 'frente' : 'lado';
        }

        function todasAsPecas(f, montagem) {
            return (f.fixas || []).map(p => Object.assign({ fixo: true }, p)).concat(montagem);
        }

        function indexar(pecas) {
            const mapa = new Map();
            pecas.forEach(p => mapa.set(chave(p.x, p.y), p));
            return mapa;
        }

        /** Célula livre para instalar uma peça: chão vazio ou fosso descoberto. */
        function podeConstruir(f, pecas, x, y) {
            if (!dentro(f, x, y) || obstaculoEm(f, x, y) || ehEntrada(f, x, y)) return false;
            const c = coletorEm(f, x, y);
            if (c && c.tipo === 'final') return false;
            return !pecas.some(p => p.x === x && p.y === y);
        }

        function contarPecas(montagem) {
            const n = {};
            montagem.forEach(p => { n[p.tipo] = (n[p.tipo] || 0) + 1; });
            return n;
        }

        function mensagemDeFalha(f, p, motivo, extra) {
            const quem = `O pacote nº ${p.id + 1} (${TAMANHOS[p.largura]})`;
            const destino = f.coletores.find(c => c.id === p.destino);
            switch (motivo) {
                case 'coletor_errado':
                    if (extra.viaQueda) return `${quem} caiu no coletor ${extra.coletor}, mas deveria ir para o coletor ${p.destino}.`;
                    if (destino && destino.tipo === 'fosso') return `${quem} seguiu pela esteira até o coletor ${extra.coletor}, mas deveria cair no coletor ${p.destino}.`;
                    return `${quem} chegou ao coletor ${extra.coletor}, mas deveria ir para o coletor ${p.destino}.`;
                case 'chao': return `${quem} caiu pelo vão do separador, mas não há coletor embaixo dele.`;
                case 'sem_esteira': return `${quem} saiu da esteira e caiu no chão: falta uma peça nesta célula.`;
                case 'saiu': return `${quem} saiu do tabuleiro.`;
                case 'obstaculo': return `${quem} bateu em um obstáculo.`;
                case 'conexao':
                    if (extra.peca === 'entrada') return `${quem} voltou para a entrada.`;
                    return `${quem} entrou ${NA_PECA[extra.peca]} pelo lado errado: gire a peça para que a seta continue o caminho.`;
                case 'colisao': return `${quem} bateu no pacote nº ${extra.outro + 1}.`;
                case 'preso': return `${quem} ficou andando em círculos.`;
            }
            return `${quem} não chegou ao destino.`;
        }

        function dicaDeFalha(f, montagem, motivo) {
            if (motivo !== 'coletor_errado') return null;
            const tipos = todasAsPecas(f, montagem).map(p => p.tipo);
            if (tipos.includes('desviador')) return 'O desviador alterna: manda um grupo de pacotes para a frente e o grupo seguinte para o lado. Confira o tamanho do grupo e por onde ele começa.';
            if (f.estoque.separador) return 'Um pacote cai quando a largura dele (P = 1, M = 2, G = 3) é menor que a abertura; largura igual à abertura continua apoiada.';
            return null;
        }

        /**
         * Simulação determinística por ticks. Cada frame guarda a posição lógica de todos os pacotes;
         * o renderizador só interpola entre frames. Para no primeiro problema ou quando todos chegam.
         */
        function simular(f, montagem) {
            const pecas = indexar(todasAsPecas(f, montagem));
            const contagem = {};
            const espaco = f.espaco || ESPACO_PADRAO;
            const pacotes = f.pacotes.map((p, i) => Object.assign({}, p, { id: i, x: f.entrada.x, y: f.entrada.y, m: f.entrada.dir, estado: 'fila', coletor: null, queda: undefined }));
            const total = pacotes.length;
            const frames = [];
            let entregues = 0;
            let falha = null;

            const foto = (eventos) => ({
                pacotes: pacotes.map(p => ({ id: p.id, x: p.x, y: p.y, estado: p.estado, coletor: p.coletor })),
                desvio: Object.assign({}, contagem),
                entregues,
                eventos,
            });
            const falhar = (p, motivo, x, y, extra) => {
                if (falha) return;
                p.estado = 'falhou';
                falha = { pacote: p.id, motivo, x, y, mensagem: mensagemDeFalha(f, p, motivo, extra || {}), dica: dicaDeFalha(f, montagem, motivo) };
            };

            const avancar = (p, ev) => {
                const nx = p.x + DIRS[p.m][0];
                const ny = p.y + DIRS[p.m][1];
                if (!dentro(f, nx, ny)) { p.x = nx; p.y = ny; return falhar(p, 'saiu', nx, ny); }
                if (obstaculoEm(f, nx, ny)) return falhar(p, 'obstaculo', nx, ny);
                if (ehEntrada(f, nx, ny)) return falhar(p, 'conexao', nx, ny, { peca: 'entrada' });
                const alvo = pecas.get(chave(nx, ny));
                const col = coletorEm(f, nx, ny);
                if (col && col.tipo === 'final') {
                    p.x = nx; p.y = ny; p.estado = 'entregue'; p.coletor = col.id;
                    ev.push({ tipo: 'entrega', pacote: p.id });
                    if (col.id !== p.destino) return falhar(p, 'coletor_errado', nx, ny, { coletor: col.id });
                    entregues++;
                    return;
                }
                if (alvo) {
                    if (direcaoDeEntrada(alvo) !== p.m) return falhar(p, 'conexao', nx, ny, { peca: alvo.tipo });
                    p.x = nx; p.y = ny;
                    if (alvo.tipo === 'separador' && p.largura < alvo.abertura) {
                        p.estado = 'caindo'; p.queda = col;
                        ev.push({ tipo: 'separa', pacote: p.id });
                        return;
                    }
                    if (alvo.tipo === 'desviador') {
                        const k = chave(nx, ny);
                        const n = contagem[k] || 0;
                        contagem[k] = n + 1;
                        const saida = saidaDoDesviador(alvo, n);
                        p.m = saida === 'frente' ? alvo.dir : lateral(alvo.dir, alvo.lado);
                        ev.push({ tipo: 'desvio', pacote: p.id, saida });
                        return;
                    }
                    p.m = alvo.dir;
                    return;
                }
                p.x = nx; p.y = ny;
                if (col) { p.estado = 'caindo'; p.queda = col; return; }
                falhar(p, 'sem_esteira', nx, ny);
            };

            frames.push(foto([]));
            for (let t = 1; t <= LIMITE_TICKS; t++) {
                const ev = [];
                for (const p of pacotes) {
                    if (falha) break;
                    if (p.estado === 'caindo') {
                        if (!p.queda) { falhar(p, 'chao', p.x, p.y); break; }
                        p.estado = 'caiu'; p.coletor = p.queda.id;
                        ev.push({ tipo: 'queda', pacote: p.id });
                        if (p.queda.id !== p.destino) falhar(p, 'coletor_errado', p.x, p.y, { coletor: p.queda.id, viaQueda: true });
                        else entregues++;
                        continue;
                    }
                    if (p.estado === 'esteira') avancar(p, ev);
                }
                if (!falha) {
                    for (const p of pacotes) {
                        if (p.estado === 'fila' && t === 1 + p.id * espaco) { p.estado = 'esteira'; ev.push({ tipo: 'saida', pacote: p.id }); }
                    }
                    const ocupado = new Map();
                    for (const p of pacotes) {
                        if (p.estado !== 'esteira' && p.estado !== 'caindo') continue;
                        const k = chave(p.x, p.y);
                        if (ocupado.has(k)) { falhar(p, 'colisao', p.x, p.y, { outro: ocupado.get(k) }); break; }
                        ocupado.set(k, p.id);
                    }
                }
                if (!falha && t === LIMITE_TICKS) {
                    const preso = pacotes.find(p => p.estado === 'esteira' || p.estado === 'caindo');
                    if (preso) falhar(preso, 'preso', preso.x, preso.y);
                }
                frames.push(foto(ev));
                if (falha || entregues === total) break;
            }

            return { frames, vitoria: !falha && entregues === total, entregues, total, falha };
        }

        /**
         * Conexões sinalizadas antes da execução: 'erro' (incompatível) e 'aberto' (ainda sem continuação).
         * Não impedem o teste de montagens incompletas.
         */
        function avisosDeConexao(f, montagem) {
            const pecas = todasAsPecas(f, montagem);
            const mapa = indexar(pecas);
            const lista = [];
            const checar = (x, y, dir) => {
                const nx = x + DIRS[dir][0];
                const ny = y + DIRS[dir][1];
                let tipo = null;
                let texto = '';
                if (!dentro(f, nx, ny)) { tipo = 'erro'; texto = 'aponta para fora do tabuleiro'; }
                else if (obstaculoEm(f, nx, ny)) { tipo = 'erro'; texto = 'aponta para um obstáculo'; }
                else if (ehEntrada(f, nx, ny)) { tipo = 'erro'; texto = 'aponta para a entrada'; }
                else {
                    const alvo = mapa.get(chave(nx, ny));
                    const col = coletorEm(f, nx, ny);
                    if (col && col.tipo === 'final') return;
                    if (alvo) {
                        if (direcaoDeEntrada(alvo) !== dir) { tipo = 'erro'; texto = 'não encaixa na próxima peça'; }
                    } else if (!col) { tipo = 'aberto'; texto = 'ainda sem continuação'; }
                }
                if (tipo) lista.push({ x, y, dir, tipo, texto });
            };
            checar(f.entrada.x, f.entrada.y, f.entrada.dir);
            pecas.forEach(p => saidasDaPeca(p).forEach(d => checar(p.x, p.y, d)));
            pecas.filter(p => p.tipo === 'separador').forEach(p => {
                const c = coletorEm(f, p.x, p.y);
                if (!c || c.tipo !== 'fosso') lista.push({ x: p.x, y: p.y, dir: null, tipo: 'erro', texto: 'separador fora de um fosso: o que cair vai ao chão' });
            });
            return lista;
        }

        /** Medalhas opcionais: nunca bloqueiam o avanço. */
        function medalhasDaFase(f, montagem, dePrimeira) {
            return { concluida: true, economica: montagem.length <= f.referencia.length, primeira: !!dePrimeira };
        }
        // ENGENHO-LOGICA-FIM
    </script>

    <script>
        // ---------- Renderizador SVG isométrico ----------
        // sx = (x − y) · L/2 e sy = (x + y) · A/2 − z; a câmera (viewBox) não interfere na lógica.
        const MEIA_L = 40;
        const MEIA_A = 20;
        const ALTURA_ESTEIRA = 20;
        const COR_COLETOR = { A: '#f08a24', B: '#8f5fd8', C: '#1fb3c4' };
        const SIMBOLO_COLETOR = { A: '▲', B: '●', C: '■' };

        function proj(fx, fy, z) { return [(fx - fy) * MEIA_L, (fx + fy) * MEIA_A - z]; }
        function pts(lista) { return lista.map(q => { const p = proj(q[0], q[1], q[2]); return p[0].toFixed(1) + ',' + p[1].toFixed(1); }).join(' '); }
        function poli(lista, fill, extra) { return `<polygon points="${pts(lista)}" fill="${fill}" ${extra || ''}/>`; }

        function tom(hex, f) {
            const n = parseInt(hex.slice(1), 16);
            const c = [n >> 16, (n >> 8) & 255, n & 255].map(v => Math.max(0, Math.min(255, Math.round(f >= 0 ? v + (255 - v) * f : v * (1 + f)))));
            return '#' + c.map(v => v.toString(16).padStart(2, '0')).join('');
        }
        function cores(base) { return { topo: tom(base, 0.18), dir: base, esq: tom(base, -0.28) }; }

        function caixa(x0, y0, x1, y1, z0, z1, c, extra) {
            const e = extra || '';
            return poli([[x1, y0, z1], [x1, y1, z1], [x1, y1, z0], [x1, y0, z0]], c.dir, e)
                + poli([[x0, y1, z1], [x1, y1, z1], [x1, y1, z0], [x0, y1, z0]], c.esq, e)
                + poli([[x0, y0, z1], [x1, y0, z1], [x1, y1, z1], [x0, y1, z1]], c.topo, e);
        }

        /** Coordenadas canônicas da peça (u = sentido do movimento, v = lateral) → célula, com giro e espelho. */
        function transformador(p) {
            return (u, v) => {
                if (p.lado === 'E') v = 1 - v;
                switch (p.dir) {
                    case 0: return [p.x + u, p.y + v];
                    case 1: return [p.x + 1 - v, p.y + u];
                    case 2: return [p.x + 1 - u, p.y + 1 - v];
                    default: return [p.x + v, p.y + 1 - u];
                }
            };
        }
        function caixaC(T, u0, v0, u1, v1, z0, z1, c) {
            const a = T(u0, v0), b = T(u1, v1);
            return caixa(Math.min(a[0], b[0]), Math.min(a[1], b[1]), Math.max(a[0], b[0]), Math.max(a[1], b[1]), z0, z1, c);
        }
        function poliC(T, lista, z, fill, extra) { return poli(lista.map(q => { const r = T(q[0], q[1]); return [r[0], r[1], z]; }), fill, extra); }
        function setaC(T, cu, cv, du, dv, z, fill) {
            const nu = -dv, nv = du;
            return poliC(T, [
                [cu + du * 0.17, cv + dv * 0.17],
                [cu - du * 0.1 + nu * 0.14, cv - dv * 0.1 + nv * 0.14],
                [cu - du * 0.01, cv - dv * 0.01],
                [cu - du * 0.1 - nu * 0.14, cv - dv * 0.1 - nv * 0.14],
            ], z, fill, 'stroke="rgba(0,0,0,.35)" stroke-width=".8"');
        }
        function etiqueta(x, y, z, texto, fundo, corTexto) {
            const p = proj(x, y, z);
            const l = 8 + texto.length * 6.4;
            return `<g transform="translate(${p[0].toFixed(1)},${p[1].toFixed(1)})"><rect x="${-l / 2}" y="-9" width="${l}" height="18" rx="6" fill="${fundo}" stroke="rgba(0,0,0,.35)"/>`
                + `<text text-anchor="middle" dominant-baseline="central" font-size="11" font-weight="800" fill="${corTexto || '#fff'}">${texto}</text></g>`;
        }

        const MOLDURA = { topo: '#a4adb7', dir: '#737d88', esq: '#5b646e' };
        const ESTEIRA = '#3a3f47';
        function trilho(fixo) { return fixo ? { topo: '#c3c8ce', dir: '#8f969d', esq: '#767d84' } : { topo: '#f6c445', dir: '#d9961f', esq: '#b47712' }; }

        function trilhosRetos(T, fixo, u0, u1) {
            return caixaC(T, u0, 0.08, u1, 0.2, ALTURA_ESTEIRA, ALTURA_ESTEIRA + 4, trilho(fixo)) + caixaC(T, u0, 0.8, u1, 0.92, ALTURA_ESTEIRA, ALTURA_ESTEIRA + 4, trilho(fixo));
        }
        /**
         * Setas na face vertical da base (entre os pontos canônicos a → b, no sentido do movimento).
         * Só desenha se a face estiver voltada para a câmera (normal para +x ou +y): `normal` é o vetor canônico para fora.
         */
        function setasLaterais(T, a, b, normal, cor) {
            const c = T(0.5, 0.5), n = T(0.5 + normal[0] * 0.1, 0.5 + normal[1] * 0.1);
            if (n[0] - c[0] < 0.01 && n[1] - c[1] < 0.01) return '';
            const du = b[0] - a[0], dv = b[1] - a[1];
            const zc = ALTURA_ESTEIRA / 2, h = ALTURA_ESTEIRA * 0.3;
            let s = '';
            [0.3, 0.72].forEach(k => {
                const m = [a[0] + du * k, a[1] + dv * k];
                const q = (t, z) => { const r = T(m[0] + du * t, m[1] + dv * t); return [r[0], r[1], z]; };
                s += poli([q(0.1, zc), q(-0.05, zc + h), q(0.01, zc), q(-0.05, zc - h)], cor, 'stroke="rgba(0,0,0,.3)" stroke-width=".6"');
            });
            return s;
        }
        function listras(T, z) {
            let s = '';
            for (let u = 0.1; u < 1; u += 0.2) s += poliC(T, [[u, 0.22], [u + 0.04, 0.22], [u + 0.04, 0.78], [u, 0.78]], z, '#2d3138');
            return s;
        }

        /** Peça inteira (ícones, fantasma): fundo + frente. */
        function desenharPeca(p, estado, coletor) {
            const partes = desenharPartes(p, estado, coletor);
            return partes.fundo + partes.frente;
        }

        /** Profundidade de tela (x + y) de um ponto canônico da peça: maior = mais perto da câmera. */
        function profundidadeC(T, u, v) { const q = T(u, v); return q[0] + q[1]; }

        /**
         * Desenha uma peça em duas partes: `fundo` (base, esteira e o que fica atrás do pacote) e `frente`
         * (poste da frente e barra do arco, caixa do desviador quando está do lado da câmera, etiquetas),
         * que a cena desenha depois do pacote que está na mesma célula. `estado` = próxima saída do desviador;
         * `coletor` = fosso coberto pela peça.
         */
        function desenharPartes(p, estado, coletor) {
            const T = transformador(p);
            const z = ALTURA_ESTEIRA;
            let s = '';
            let f = '';
            if (p.tipo === 'reta') {
                s += caixaC(T, 0, 0.08, 1, 0.92, 0, z, MOLDURA) + setasLaterais(T, [0, 0.08], [1, 0.08], [0, -1], '#f6c445') + setasLaterais(T, [0, 0.92], [1, 0.92], [0, 1], '#f6c445');
                s += poliC(T, [[0, 0.2], [1, 0.2], [1, 0.8], [0, 0.8]], z, ESTEIRA) + listras(T, z + 0.1);
                s += trilhosRetos(T, p.fixo, 0, 1);
                s += setaC(T, 0.5, 0.5, 1, 0, z + 0.3, '#f6c445');
            } else if (p.tipo === 'curva') {
                // Arco entre a borda de entrada (v = 1) e a de saída (u = 1), centrado no canto (1, 1).
                s += caixaC(T, 0.08, 0.08, 1, 1, 0, z, MOLDURA);
                s += setasLaterais(T, [0.08, 0.08], [1, 0.08], [0, -1], '#f6c445') + setasLaterais(T, [0.08, 1], [0.08, 0.08], [-1, 0], '#f6c445');
                const arco = (r0, r1) => {
                    const fora = [], dentroA = [];
                    for (let i = 0; i <= 10; i++) {
                        const a = Math.PI + (Math.PI / 2) * (i / 10);
                        fora.push([1 + r1 * Math.cos(a), 1 + r1 * Math.sin(a)]);
                        dentroA.unshift([1 + r0 * Math.cos(a), 1 + r0 * Math.sin(a)]);
                    }
                    return fora.concat(dentroA);
                };
                s += poliC(T, arco(0.2, 0.8), z, ESTEIRA);
                const t = trilho(p.fixo).topo;
                s += poliC(T, arco(0.8, 0.92), z + 3, t, 'stroke="rgba(0,0,0,.25)" stroke-width=".6"');
                s += poliC(T, arco(0.08, 0.2), z + 3, t, 'stroke="rgba(0,0,0,.25)" stroke-width=".6"');
                s += setaC(T, 0.646, 0.646, 0.707, -0.707, z + 0.3, '#f6c445');
            } else if (p.tipo === 'separador') {
                const g = 0.06 + (p.abertura || 1) * 0.05;
                s += caixaC(T, 0, 0.08, 1, 0.92, 0, z, MOLDURA) + setasLaterais(T, [0, 0.08], [1, 0.08], [0, -1], '#f6c445') + setasLaterais(T, [0, 0.92], [1, 0.92], [0, 1], '#f6c445');
                s += poliC(T, [[0, 0.2], [1, 0.2], [1, 0.5 - g], [0, 0.5 - g]], z, ESTEIRA);
                s += poliC(T, [[0, 0.5 + g], [1, 0.5 + g], [1, 0.8], [0, 0.8]], z, ESTEIRA);
                s += poliC(T, [[0.06, 0.5 - g], [0.94, 0.5 - g], [0.94, 0.5 + g], [0.06, 0.5 + g]], z + 0.1, '#0f1114');
                s += trilhosRetos(T, p.fixo, 0, 1);
                s += setaC(T, 0.2, 0.5, 1, 0, z + 0.3, '#f6c445');
                const laranja = cores('#ee7a2b');
                const posteA = caixaC(T, 0.42, 0.02, 0.58, 0.12, z, z + 32, laranja);
                const posteB = caixaC(T, 0.42, 0.88, 0.58, 0.98, z, z + 32, laranja);
                // O pacote passa entre os postes e por baixo da barra: poste de trás no fundo, o resto na frente.
                const aNaFrente = profundidadeC(T, 0.5, 0.07) > profundidadeC(T, 0.5, 0.93);
                s += aNaFrente ? posteB : posteA;
                f += aNaFrente ? posteA : posteB;
                f += caixaC(T, 0.42, 0.02, 0.58, 0.98, z + 32, z + 38, laranja);
                const c = T(0.5, 0.5);
                f += etiqueta(c[0], c[1], z + 52, 'cai < ' + (p.abertura || 1) + (coletor ? ' → ' + SIMBOLO_COLETOR[coletor] + ' ' + coletor : ''), coletor ? COR_COLETOR[coletor] : '#1b232d', coletor ? '#fff' : '#ffd27a');
            } else if (p.tipo === 'desviador') {
                s += caixaC(T, 0, 0.08, 1, 1, 0, z, MOLDURA) + setasLaterais(T, [0, 0.08], [1, 0.08], [0, -1], '#5ee06e');
                s += poliC(T, [[0, 0.2], [1, 0.2], [1, 0.8], [0, 0.8]], z, ESTEIRA);
                s += poliC(T, [[0.2, 0.5], [0.8, 0.5], [0.8, 1], [0.2, 1]], z + 0.05, ESTEIRA);
                s += caixaC(T, 0, 0.08, 1, 0.2, z, z + 4, trilho(p.fixo));
                s += caixaC(T, 0, 0.8, 0.2, 0.92, z, z + 4, trilho(p.fixo)) + caixaC(T, 0.8, 0.8, 1, 0.92, z, z + 4, trilho(p.fixo));
                const caixaDesv = caixaC(T, 0.3, 0, 0.7, 0.16, z, z + 24, cores('#6f8396'));
                if (profundidadeC(T, 0.5, 0.08) > profundidadeC(T, 0.5, 0.5)) f += caixaDesv; else s += caixaDesv;
                const frente = (estado || 'frente') === 'frente';
                s += setaC(T, 0.62, 0.5, 1, 0, z + 0.3, frente ? '#5ee06e' : '#55606b');
                s += setaC(T, 0.5, 0.66, 0, 1, z + 0.35, frente ? '#55606b' : '#5ee06e');
                const c = T(0.5, 0.08);
                const ordem = p.primeiro === 'lado' ? 'L→F' : 'F→L';
                f += etiqueta(c[0], c[1], z + 38, ordem + ' ×' + (p.grupo || 1), '#1b232d', '#9df0a8');
            }
            return { fundo: s, frente: f };
        }

        // Máquina de entrada: alta o bastante para o pacote G (27) passar pela abertura (z1 = esteira + 32).
        const ALTURA_ENTRADA = ALTURA_ESTEIRA + 40;

        function desenharEntrada(e) {
            const { x, y } = e;
            let s = caixa(x + 0.05, y + 0.05, x + 0.95, y + 0.95, 0, ALTURA_ENTRADA, cores('#5d6b7a'));
            const z0 = ALTURA_ESTEIRA, z1 = ALTURA_ESTEIRA + 32;
            if (e.dir === 0) s += poli([[x + 0.95, y + 0.25, z0], [x + 0.95, y + 0.75, z0], [x + 0.95, y + 0.75, z1], [x + 0.95, y + 0.25, z1]], '#15191e');
            if (e.dir === 1) s += poli([[x + 0.25, y + 0.95, z0], [x + 0.75, y + 0.95, z0], [x + 0.75, y + 0.95, z1], [x + 0.25, y + 0.95, z1]], '#15191e');
            s += poli([[x + 0.05, y + 0.05, ALTURA_ENTRADA + 0.2], [x + 0.95, y + 0.05, ALTURA_ENTRADA + 0.2], [x + 0.95, y + 0.2, ALTURA_ENTRADA + 0.2], [x + 0.05, y + 0.2, ALTURA_ENTRADA + 0.2]], '#f6c445');
            const T = transformador({ x, y, dir: e.dir });
            s += setaC(T, 0.5, 0.55, 1, 0, ALTURA_ENTRADA + 0.4, '#5ee06e');
            return s;
        }

        /** Coletor final em duas partes: a caixa aberta (fundo) e as paredes da frente, que cobrem o pacote que cai dentro. */
        function desenharColetorFinal(c, contagem) {
            const { x, y } = c;
            const base = COR_COLETOR[c.id] || '#888';
            const k = cores(base);
            const x0 = x + 0.08, x1 = x + 0.92, y0 = y + 0.08, y1 = y + 0.92, h = 30;
            let s = caixa(x0, y0, x1, y1, 0, h, k);
            s += poli([[x + 0.16, y + 0.16, h], [x + 0.84, y + 0.16, h], [x + 0.84, y + 0.84, h], [x + 0.16, y + 0.84, h]], tom(base, -0.6));
            let f = poli([[x1, y0, h], [x1, y1, h], [x1, y1, 0], [x1, y0, 0]], k.dir) + poli([[x0, y1, h], [x1, y1, h], [x1, y1, 0], [x0, y1, 0]], k.esq);
            f += poli([[x + 0.84, y0, h], [x1, y0, h], [x1, y1, h], [x0, y1, h], [x0, y + 0.84, h], [x + 0.84, y + 0.84, h]], k.topo);
            f += etiqueta(x + 0.5, y + 0.5, 54, SIMBOLO_COLETOR[c.id] + ' ' + c.id + (contagem ? ' · ' + contagem : ''), base);
            return { fundo: s, frente: f };
        }

        function desenharFosso(c) {
            const { x, y } = c;
            const base = COR_COLETOR[c.id] || '#888';
            // Buraco recortado na abertura (clipPath): só aparecem o fundo e as duas paredes de trás, como num poço visto de cima.
            const a = 0.13, b = 0.87, fundo = -26;
            const abertura = [[x + a, y + a, 0.3], [x + b, y + a, 0.3], [x + b, y + b, 0.3], [x + a, y + b, 0.3]];
            const id = 'fosso-' + c.id;
            return poli([[x + 0.04, y + 0.04, 0.2], [x + 0.96, y + 0.04, 0.2], [x + 0.96, y + 0.96, 0.2], [x + 0.04, y + 0.96, 0.2]], base)
                + `<clipPath id="${id}"><polygon points="${pts(abertura)}"/></clipPath><g clip-path="url(#${id})">`
                + poli(abertura, tom(base, -0.72))
                + poli([[x + a, y + a, fundo], [x + b, y + a, fundo], [x + b, y + b, fundo], [x + a, y + b, fundo]], tom(base, -0.45))
                + poli([[x + a, y + a, 0.3], [x + b, y + a, 0.3], [x + b, y + a, fundo], [x + a, y + a, fundo]], '#2b3038')
                + poli([[x + a, y + a, 0.3], [x + a, y + b, 0.3], [x + a, y + b, fundo], [x + a, y + a, fundo]], '#3b414b')
                + '</g>'
                + poli(abertura, 'none', `stroke="${tom(base, -0.35)}" stroke-width="1.5" stroke-linejoin="round"`);
        }

        // Carimbos das caixas de enfeite (easter eggs): texto na face e uma frase no hover.
        const CARIMBOS = [
            ['FRÁGIL', 'Frágil. Igual ao orçamento de peças desta fase.'],
            ['ÔNIBUS 42', 'Peças de reposição do Terminal. Ônibus de 4 lugares que nunca enche.'],
            ['MITHRIL', 'Contém um anel. Não declarar à Receita.'],
            ['YOMI VOL.7', 'Volume 7 de um mangá que ainda não foi lançado.'],
            ['CARDÁPIO', 'Marmitas da semana. Validade: ontem.'],
            ['ALFRED', 'Lembrete: beba água. Você está montando esteiras há um tempão.'],
            ['PIDGEY', 'Correio expresso. Entrega feita por pombo.'],
            ['NÃO ABRA', 'Sério. Não abra.'],
            ['BOLÃO', 'Palpites da reunião de segunda. Ninguém acertou.'],
            ['42', 'A resposta. A pergunta está em outra caixa.'],
            ['CTRL+Z', 'Desfazer não funciona em caixas de madeira.'],
            ['SEM PRESSA', 'Pilar de design nº 1: pensar sem pressa.'],
            ['TREETASK', 'Tarefa: arrumar este galpão. Status: em andamento desde 2026.'],
            ['ESTE LADO ↑', 'Instalada de cabeça para baixo, como manda a tradição.'],
            ['SPIGO.NET', 'Feito à mão em Engenhocópolis.'],
            ['P · M · G', 'Tamanhos sortidos. Nenhum deles cai no vão.'],
        ];

        /** Caixa de enfeite de um nível só (não cobre o que está atrás), com carimbo em uma das faces visíveis. */
        function desenharObstaculo(x, y, n) {
            const base = '#b5864d';
            const h = 22;
            const [texto, frase] = CARIMBOS[n % CARIMBOS.length];
            let s = `<g><title>${frase}</title>` + caixa(x + 0.1, y + 0.1, x + 0.9, y + 0.9, 0, h, cores(base));
            s += poli([[x + 0.1, y + 0.45, h + 0.1], [x + 0.9, y + 0.45, h + 0.1], [x + 0.9, y + 0.55, h + 0.1], [x + 0.1, y + 0.55, h + 0.1]], tom(base, -0.2));
            // Face +y lê ao longo de +x; face +x lê ao longo de −y (cisalhamento isométrico do texto).
            const naEsquerda = n % 2 === 0;
            const c = naEsquerda ? proj(x + 0.5, y + 0.9, h / 2) : proj(x + 0.9, y + 0.5, h / 2);
            const tamanho = Math.min(7.5, 31 / (texto.length * 0.62)).toFixed(1);
            const m = naEsquerda ? '0.894 0.447 0 1' : '0.894 -0.447 0 1';
            s += `<text transform="matrix(${m} ${c[0].toFixed(1)} ${c[1].toFixed(1)})" text-anchor="middle" dominant-baseline="central" font-size="${tamanho}" font-weight="900" letter-spacing=".3" fill="#4a2c0e" fill-opacity=".8" font-family="ui-monospace, monospace">${texto}</text>`;
            return s + '</g>';
        }

        /**
         * Pacote como caixa isométrica. `corte` ({xmin, xmax, ymin, ymax}, opcional) recorta o volume no chão antes
         * de projetar: usado na saída da entrada, para desenhar só a parte que já passou pela face da máquina.
         */
        function desenharPacote(p, fx, fy, z, opacidade, destaque, corte) {
            const s = { 1: 0.28, 2: 0.4, 3: 0.54 }[p.largura];
            const h = s * 50;
            const c = corte || {};
            const x0 = Math.max(fx - s / 2, c.xmin ?? -Infinity), x1 = Math.min(fx + s / 2, c.xmax ?? Infinity);
            const y0 = Math.max(fy - s / 2, c.ymin ?? -Infinity), y1 = Math.min(fy + s / 2, c.ymax ?? Infinity);
            if (x1 - x0 < 0.01 || y1 - y0 < 0.01) return '';
            const inteiro = x1 - x0 > s * 0.6 && y1 - y0 > s * 0.6;
            const extra = opacidade < 1 ? `opacity="${opacidade.toFixed(2)}"` : '';
            let r = `<g ${extra}>`;
            if (destaque) r += poli([[fx - 0.45, fy - 0.45, 1], [fx + 0.45, fy - 0.45, 1], [fx + 0.45, fy + 0.45, 1], [fx - 0.45, fy + 0.45, 1]], 'none', 'stroke="#ff4d3d" stroke-width="3" class="pulsar"');
            // Cor e símbolo do coletor de destino (não depende só da cor); tamanho em cima.
            r += caixa(x0, y0, x1, y1, z, z + h, cores(COR_COLETOR[p.destino] || '#c99a5b'), 'stroke="rgba(0,0,0,.3)" stroke-width=".7"');
            if (inteiro) {
                const t = proj((x0 + x1) / 2, (y0 + y1) / 2, z + h);
                r += `<text x="${t[0].toFixed(1)}" y="${t[1].toFixed(1)}" text-anchor="middle" dominant-baseline="central" font-size="${7 + p.largura * 2}" font-weight="900" fill="#fff" stroke="rgba(0,0,0,.45)" stroke-width="2" paint-order="stroke">${LETRAS[p.largura]}</text>`;
                const lado = proj(x1, (y0 + y1) / 2, z + h / 2);
                r += `<text transform="matrix(0.894 -0.447 0 1 ${lado[0].toFixed(1)} ${lado[1].toFixed(1)})" text-anchor="middle" dominant-baseline="central" font-size="${(4 + p.largura * 2.2).toFixed(1)}" fill="#fff" fill-opacity=".9">${SIMBOLO_COLETOR[p.destino] || ''}</text>`;
            }
            return r + '</g>';
        }

        function iconeDaPeca(tipo) {
            const p = { tipo, x: 0, y: 0, dir: 0, lado: 'D', abertura: 2, grupo: 1, primeiro: 'frente' };
            let corpo = desenharPeca(p);
            corpo = corpo.replace(/<g transform=[\s\S]*?<\/g>/g, '');
            return `<svg viewBox="-44 -30 88 76" aria-hidden="true">${corpo}</svg>`;
        }

        // Ícones da barra da peça selecionada (traço = currentColor).
        const ICONE = (corpo) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${corpo}</svg>`;
        const ICONES = {
            girar: ICONE('<path d="M20 12a8 8 0 1 1-2.6-5.9"/><path d="M20 3.5v4.8h-4.8"/>'),
            espelhar: ICONE('<path d="M12 3v18" stroke-dasharray="2 2.6"/><path d="M9 7 3.5 17H9z"/><path d="M15 7l5.5 10H15z"/>'),
            remover: ICONE('<path d="M4 7h16"/><path d="M9 7V4.5h6V7"/><path d="M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/>'),
            info: ICONE('<circle cx="12" cy="12" r="9"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/>'),
            menos: ICONE('<path d="M5 12h14"/>'),
            mais: ICONE('<path d="M5 12h14M12 5v14"/>'),
            frente: ICONE('<path d="M12 20V4"/><path d="M6 10l6-6 6 6"/>'),
            ladoD: ICONE('<path d="M7 20v-7a4 4 0 0 1 4-4h9"/><path d="M15 4l5 5-5 5"/>'),
            ladoE: ICONE('<path d="M17 20v-7a4 4 0 0 0-4-4H4"/><path d="M9 4 4 9l5 5"/>'),
            cadeado: ICONE('<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>'),
        };

        // Explicação completa de cada peça (botão de informações).
        const INFO_PECA = {
            reta: [
                'Leva o pacote em linha reta, no sentido das setas amarelas.',
                'O pacote precisa entrar pela traseira (o lado oposto às setas). Se chegar pela lateral ou de frente, a linha para com erro de conexão.',
                'Girar muda o sentido. Instalada sobre um fosso, ela cobre o buraco e os pacotes passam por cima.',
            ],
            curva: [
                'Vira o pacote em 90°.',
                'Ela recebe por um lado e entrega pelo outro, seguindo as setas. Girar muda para onde ela aponta; Espelhar troca a curva entre esquerda e direita.',
                'Se o pacote chegar pelo lado errado, a linha para com erro de conexão.',
            ],
            separador: [
                'Esteira com um vão no meio, de abertura ajustável.',
                'Pacotes com largura MENOR que a abertura caem pelo vão; os de largura igual ou maior seguem apoiados nas bordas. Larguras: P = 1, M = 2, G = 3.',
                'Precisa ficar sobre um fosso: é o fosso que recebe quem cai. Fora de um fosso, o pacote cai no chão e a linha para.',
                'Com dois separadores em sequência dá para dividir três tamanhos: o primeiro tira os menores.',
            ],
            desviador: [
                'Divide a linha em duas saídas: a da frente e a lateral.',
                'Ele não mede os pacotes, só conta: manda um grupo para uma saída, o grupo seguinte para a outra, e vai alternando.',
                'Escolha por qual saída ele começa (frente ou lado) e o tamanho do grupo (×1, ×2, ×3). Espelhar troca o lado da saída lateral.',
                'Olhe a fila: se os destinos vêm em pares (A, A, B, B…), use grupo ×2.',
            ],
        };

        // ---------- Persistência local (versionada) ----------
        const CHAVE_SALVO = 'engenhocopolis:v1';
        function salvoPadrao() {
            const reduzir = typeof matchMedia !== 'undefined' && matchMedia('(prefers-reduced-motion: reduce)').matches;
            return { versao: 1, desbloqueada: 1, resultados: {}, montagens: {}, falhas: {}, introVista: {}, prefs: { som: true, reduzir, velocidade: 1 } };
        }
        function carregarSalvo() {
            try {
                const bruto = JSON.parse(localStorage.getItem(CHAVE_SALVO) || 'null');
                if (!bruto || bruto.versao !== 1) return salvoPadrao();
                const base = salvoPadrao();
                return Object.assign(base, bruto, { prefs: Object.assign(base.prefs, bruto.prefs || {}) });
            } catch (e) {
                return salvoPadrao();
            }
        }

        // ---------- Componente Alpine ----------
        function engenhocopolis() {
            // Camadas do SVG guardadas em iniciar(): um $refs lido a partir de um botão dentro de <template x-if>
            // que já saiu do DOM (ex.: "Próxima fase" num $nextTick) volta vazio.
            const dom = {};
            return {
                FASES, SETORES, TIPOS_PECA, NOMES_PECA, LETRAS, TAMANHOS, COR_COLETOR, SIMBOLO_COLETOR, ICONES, INFO_PECA,
                infoPeca: false,
                tela: 'mapa',
                salvo: carregarSalvo(),
                idx: 0,
                montagem: [],
                historico: [],
                ferramenta: null,
                orientacao: { dir: 0, lado: 'D' },
                selecionada: null,
                modo: 'edicao',
                sim: null,
                tick: 0,
                rodando: false,
                resultado: null,
                mostrarIntro: false,
                ajuda: false,
                icones: {},
                cam: null,
                enquadro: null,
                ponteiros: new Map(),
                gesto: null,
                espaco: false,
                hover: null,
                arrasto: null,
                ultimoTick: -1,
                audio: null,
                avisos: [],
                cacheCena: null,
                selDesenhada: null,

                iniciar() {
                    ['svg', 'chao', 'cena', 'marcas'].forEach(nome => { dom[nome] = this.$refs[nome]; });
                    TIPOS_PECA.forEach(t => { this.icones[t] = iconeDaPeca(t); });
                    window.addEventListener('resize', () => { if (this.tela === 'jogo') { this.enquadrar(); this.desenharCena(); } });
                },

                get fase() { return FASES[this.idx]; },
                setorDe(i) { return FASES.map((f, n) => n).filter(n => FASES[n].setor === i); },
                liberada(n) { return n + 1 <= this.salvo.desbloqueada; },
                medalhas(n) { return this.salvo.resultados[n + 1] || {}; },

                salvar() {
                    try { localStorage.setItem(CHAVE_SALVO, JSON.stringify(this.salvo)); } catch (e) { /* armazenamento indisponível: segue sem salvar */ }
                },
                salvarMontagem() {
                    this.salvo.montagens[this.idx + 1] = this.montagem.map(p => Object.assign({}, p));
                    this.salvar();
                },

                abrirFase(n) {
                    if (!this.liberada(n)) return;
                    this.idx = n;
                    this.montagem = (this.salvo.montagens[n + 1] || []).map(p => Object.assign({}, p));
                    this.historico = [];
                    this.ferramenta = null;
                    this.selecionada = null;
                    this.modo = 'edicao';
                    this.sim = null;
                    this.resultado = null;
                    this.mostrarIntro = !this.salvo.introVista[n + 1];
                    this.tela = 'jogo';
                    this.$nextTick(() => { this.enquadrar(); this.redesenharTudo(); });
                },
                fecharIntro() {
                    this.mostrarIntro = false;
                    this.salvo.introVista[this.idx + 1] = true;
                    this.salvar();
                },
                voltarAoMapa() {
                    this.pararExecucao();
                    this.tela = 'mapa';
                },
                proximaFase() {
                    if (this.idx + 1 < FASES.length) this.abrirFase(this.idx + 1);
                    else this.voltarAoMapa();
                },

                // ----- Inventário e edição -----
                restante(tipo) { return (this.fase.estoque[tipo] || 0) - (contarPecas(this.montagem)[tipo] || 0); },
                tiposDaFase() { return TIPOS_PECA.filter(t => this.fase.estoque[t]); },
                pecasNoTabuleiro() { return todasAsPecas(this.fase, this.montagem); },
                pecaEm(x, y) { return this.pecasNoTabuleiro().find(p => p.x === x && p.y === y) || null; },
                get pecaSelecionada() { return this.selecionada ? this.pecaEm(this.selecionada.x, this.selecionada.y) : null; },
                lembrar() {
                    this.historico.push(JSON.stringify(this.montagem));
                    if (this.historico.length > 60) this.historico.shift();
                },
                mudou() {
                    this.salvarMontagem();
                    this.redesenharTudo();
                },
                escolherFerramenta(tipo) {
                    if (this.modo !== 'edicao') return;
                    this.ferramenta = this.ferramenta === tipo ? null : tipo;
                    this.som('girar');
                    this.desenharMarcas();
                },
                instalar(x, y) {
                    const tipo = this.ferramenta;
                    if (!tipo || this.restante(tipo) <= 0 || !podeConstruir(this.fase, this.pecasNoTabuleiro(), x, y)) return false;
                    this.lembrar();
                    const nova = peca(tipo, x, y, this.orientacao.dir);
                    if (ESPELHAVEIS.includes(tipo)) nova.lado = this.orientacao.lado;
                    if (tipo === 'separador') nova.abertura = (this.fase.limites && this.fase.limites.abertura || [1, 4])[0];
                    if (tipo === 'desviador') { nova.primeiro = 'frente'; nova.grupo = 1; }
                    this.montagem.push(nova);
                    this.selecionada = { x, y };
                    if (this.restante(tipo) <= 0) this.ferramenta = null;
                    this.som('instalar');
                    this.mudou();
                    return true;
                },
                girar() {
                    const p = this.pecaSelecionada;
                    if (p && !p.fixo && this.modo === 'edicao') {
                        this.lembrar();
                        p.dir = (p.dir + 1) % 4;
                        this.orientacao.dir = p.dir;
                        this.som('girar');
                        this.mudou();
                    } else if (this.ferramenta) {
                        this.orientacao.dir = (this.orientacao.dir + 1) % 4;
                        this.som('girar');
                        this.desenharMarcas();
                    }
                },
                podeEspelhar() {
                    const p = this.pecaSelecionada;
                    if (p && !p.fixo) return ESPELHAVEIS.includes(p.tipo);
                    return !!this.ferramenta && ESPELHAVEIS.includes(this.ferramenta);
                },
                espelhar() {
                    const p = this.pecaSelecionada;
                    if (p && !p.fixo && ESPELHAVEIS.includes(p.tipo) && this.modo === 'edicao') {
                        this.lembrar();
                        p.lado = p.lado === 'E' ? 'D' : 'E';
                        this.orientacao.lado = p.lado;
                        this.som('girar');
                        this.mudou();
                    } else if (this.ferramenta && ESPELHAVEIS.includes(this.ferramenta)) {
                        this.orientacao.lado = this.orientacao.lado === 'E' ? 'D' : 'E';
                        this.som('girar');
                        this.desenharMarcas();
                    }
                },
                remover() {
                    const p = this.pecaSelecionada;
                    if (!p || p.fixo || this.modo !== 'edicao') return;
                    this.lembrar();
                    this.montagem = this.montagem.filter(q => q !== p);
                    this.selecionada = null;
                    this.som('remover');
                    this.mudou();
                },
                limparTudo() {
                    if (!this.montagem.length || this.modo !== 'edicao') return;
                    this.lembrar();
                    this.montagem = [];
                    this.selecionada = null;
                    this.mudou();
                },
                desfazer() {
                    if (!this.historico.length || this.modo !== 'edicao') return;
                    this.montagem = JSON.parse(this.historico.pop());
                    if (this.selecionada && !this.pecaEm(this.selecionada.x, this.selecionada.y)) this.selecionada = null;
                    this.som('girar');
                    this.mudou();
                },
                limite(nome) { return (this.fase.limites && this.fase.limites[nome]) || (nome === 'grupo' ? [1, 3] : [1, 4]); },
                ajustar(campo, delta) {
                    const p = this.pecaSelecionada;
                    if (!p || p.fixo || this.modo !== 'edicao') return;
                    const [min, max] = this.limite(campo);
                    const novo = Math.max(min, Math.min(max, (p[campo] || min) + delta));
                    if (novo === p[campo]) return;
                    this.lembrar();
                    p[campo] = novo;
                    this.som('girar');
                    this.mudou();
                },
                definir(campo, valor) {
                    const p = this.pecaSelecionada;
                    if (!p || p.fixo || this.modo !== 'edicao' || p[campo] === valor) return;
                    this.lembrar();
                    p[campo] = valor;
                    this.som('girar');
                    this.mudou();
                },
                descreverAbertura(a) {
                    const caem = [1, 2, 3].filter(l => l < a).map(l => LETRAS[l]);
                    if (!caem.length) return 'Nenhum pacote cai: todos seguem pela esteira.';
                    if (caem.length === 3) return 'Todos os pacotes caem no vão.';
                    return 'Caem: ' + caem.join(' e ') + '. Seguem: ' + [1, 2, 3].filter(l => l >= a).map(l => LETRAS[l]).join(' e ') + '.';
                },
                descreverDesviador(p) {
                    const g = p.grupo || 1;
                    const a = p.primeiro === 'lado' ? 'para o lado' : 'para a frente';
                    const b = p.primeiro === 'lado' ? 'para a frente' : 'para o lado';
                    return `${g === 1 ? 'O 1º pacote vai' : 'Os ' + g + ' primeiros vão'} ${a}, ${g === 1 ? 'o seguinte' : 'os ' + g + ' seguintes'} ${b}, e assim por diante.`;
                },

                // ----- Execução -----
                executar() {
                    if (this.modo === 'execucao') { this.alternarPausa(); return; }
                    this.mostrarIntro = false;
                    this.ferramenta = null;
                    this.selecionada = null;
                    this.sim = simular(this.fase, this.montagem);
                    this.modo = 'execucao';
                    this.resultado = null;
                    this.tick = 0;
                    this.ultimoTick = 0;
                    this.rodando = true;
                    this.som('ligar');
                    this.desenharMarcas();
                    this.laco();
                },
                alternarPausa() {
                    if (this.modo !== 'execucao' || this.resultado) return;
                    this.rodando = !this.rodando;
                    if (this.rodando) this.laco();
                },
                passo() {
                    if (this.modo !== 'execucao') this.executar();
                    this.rodando = false;
                    const fim = this.sim.frames.length - 1;
                    this.tick = Math.min(fim, Math.floor(this.tick) + 1);
                    this.aoCruzarTick();
                    this.desenharCena();
                    if (this.tick >= fim) this.concluir();
                },
                repetir() {
                    this.resultado = null;
                    this.tick = 0;
                    this.ultimoTick = 0;
                    this.rodando = true;
                    this.desenharCena();
                    this.laco();
                },
                pararExecucao() {
                    this.rodando = false;
                    this.modo = 'edicao';
                    this.sim = null;
                    this.resultado = null;
                },
                editar() {
                    this.pararExecucao();
                    this.redesenharTudo();
                },
                trocarVelocidade() {
                    const v = { 1: 2, 2: 4, 4: 1 }[this.salvo.prefs.velocidade] || 1;
                    this.salvo.prefs.velocidade = v;
                    this.salvar();
                },
                duracaoTick() { return (this.salvo.prefs.reduzir ? 750 : 520) / (this.salvo.prefs.velocidade || 1); },
                laco() {
                    let anterior = performance.now();
                    const quadro = (agora) => {
                        if (!this.rodando || this.modo !== 'execucao' || this.tela !== 'jogo') return;
                        const fim = this.sim.frames.length - 1;
                        // O timestamp do rAF pode ser anterior ao performance.now() inicial: nunca deixa o tick recuar.
                        this.tick = Math.min(fim, this.tick + Math.max(0, agora - anterior) / this.duracaoTick());
                        anterior = agora;
                        this.aoCruzarTick();
                        this.desenharCena();
                        if (this.tick >= fim) { this.concluir(); return; }
                        requestAnimationFrame(quadro);
                    };
                    requestAnimationFrame(quadro);
                },
                aoCruzarTick() {
                    const atual = Math.floor(this.tick);
                    while (this.ultimoTick < atual) {
                        this.ultimoTick++;
                        const fr = this.sim.frames[this.ultimoTick];
                        if (!fr) break;
                        fr.eventos.forEach(e => this.som(e.tipo));
                    }
                },
                get entreguesAgora() {
                    if (!this.sim) return 0;
                    return this.sim.frames[Math.min(this.sim.frames.length - 1, Math.floor(this.tick))].entregues;
                },
                concluir() {
                    if (this.resultado) return;
                    this.rodando = false;
                    const n = this.idx + 1;
                    if (this.sim.vitoria) {
                        const novas = medalhasDaFase(this.fase, this.montagem, !this.salvo.falhas[n]);
                        const antigas = this.salvo.resultados[n] || {};
                        this.salvo.resultados[n] = {
                            concluida: true,
                            economica: !!(antigas.economica || novas.economica),
                            primeira: !!(antigas.primeira || novas.primeira),
                            pecas: Math.min(antigas.pecas || Infinity, this.montagem.length),
                        };
                        this.salvo.desbloqueada = Math.max(this.salvo.desbloqueada, Math.min(FASES.length, n + 1));
                        this.resultado = { vitoria: true, medalhas: novas };
                        this.som('vitoria');
                    } else {
                        this.salvo.falhas[n] = true;
                        this.resultado = { vitoria: false, falha: this.sim.falha };
                        this.som('falha');
                    }
                    this.salvar();
                    this.desenharMarcas();
                },
                estadoNaFila(i) {
                    if (!this.sim) return '';
                    if (this.sim.falha && this.sim.falha.pacote === i && this.resultado) return 'erro';
                    const fr = this.sim.frames[Math.min(this.sim.frames.length - 1, Math.floor(this.tick))];
                    const e = fr.pacotes[i];
                    return (e.estado === 'entregue' || e.estado === 'caiu') && e.coletor === this.fase.pacotes[i].destino ? 'ok' : '';
                },

                // ----- Câmera (viewBox) -----
                limitesCena() {
                    const f = this.fase;
                    const xs = [proj(0, f.altura, 0)[0], proj(f.largura, 0, 0)[0]];
                    const topo = proj(0, 0, 0)[1] - 80;
                    const base = proj(f.largura, f.altura, 0)[1] + 30;
                    return { x: xs[0] - 30, y: topo, w: xs[1] - xs[0] + 60, h: base - topo };
                },
                enquadrar() {
                    const b = this.limitesCena();
                    const el = dom.svg;
                    const r = el.getBoundingClientRect();
                    const aspecto = r.width && r.height ? r.width / r.height : 1.5;
                    let w = b.w, h = b.h;
                    if (w / h < aspecto) w = h * aspecto; else h = w / aspecto;
                    this.cam = { x: b.x + b.w / 2 - w / 2, y: b.y + b.h / 2 - h / 2, w, h };
                    this.enquadro = { w, h, cx: b.x + b.w / 2, cy: b.y + b.h / 2 };
                    this.aplicarCamera();
                },
                aplicarCamera() {
                    const c = this.cam;
                    const e = this.enquadro;
                    c.w = Math.max(e.w * 0.3, Math.min(e.w * 1.6, c.w));
                    c.h = c.w * (e.h / e.w);
                    const cx = Math.max(e.cx - e.w * 0.6, Math.min(e.cx + e.w * 0.6, c.x + c.w / 2));
                    const cy = Math.max(e.cy - e.h * 0.6, Math.min(e.cy + e.h * 0.6, c.y + c.h / 2));
                    c.x = cx - c.w / 2;
                    c.y = cy - c.h / 2;
                    dom.svg.setAttribute('viewBox', `${c.x.toFixed(1)} ${c.y.toFixed(1)} ${c.w.toFixed(1)} ${c.h.toFixed(1)}`);
                },
                paraSvg(cx, cy) {
                    const el = dom.svg;
                    const pt = el.createSVGPoint();
                    pt.x = cx; pt.y = cy;
                    const m = el.getScreenCTM();
                    return m ? pt.matrixTransform(m.inverse()) : { x: 0, y: 0 };
                },
                escalaTela() { const m = dom.svg.getScreenCTM(); return m ? 1 / m.a : 1; },
                zoom(fator, cx, cy) {
                    const p = this.paraSvg(cx, cy);
                    const c = this.cam;
                    const novoW = Math.max(this.enquadro.w * 0.3, Math.min(this.enquadro.w * 1.6, c.w * fator));
                    const k = novoW / c.w;
                    c.x = p.x - (p.x - c.x) * k;
                    c.y = p.y - (p.y - c.y) * k;
                    c.w = novoW;
                    this.aplicarCamera();
                },
                zoomBotao(fator) {
                    const r = dom.svg.getBoundingClientRect();
                    this.zoom(fator, r.left + r.width / 2, r.top + r.height / 2);
                },
                celulaEm(cx, cy) {
                    const p = this.paraSvg(cx, cy);
                    const a = p.x / MEIA_L;
                    const b = (p.y + ALTURA_ESTEIRA) / MEIA_A;
                    return { x: Math.floor((a + b) / 2), y: Math.floor((b - a) / 2) };
                },

                // ----- Ponteiro: toque, mouse, pinça -----
                aoApertar(e) {
                    dom.svg.setPointerCapture(e.pointerId);
                    this.ponteiros.set(e.pointerId, { x: e.clientX, y: e.clientY });
                    if (this.ponteiros.size === 2) {
                        clearTimeout(this.gesto && this.gesto.timer);
                        const [a, b] = [...this.ponteiros.values()];
                        this.gesto = { tipo: 'pinca', dist: Math.hypot(a.x - b.x, a.y - b.y), meio: { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 } };
                        this.arrasto = null;
                        return;
                    }
                    if (this.ponteiros.size > 2) return;
                    const cel = this.celulaEm(e.clientX, e.clientY);
                    const p = this.pecaEm(cel.x, cel.y);
                    const panar = e.button === 1 || this.espaco;
                    const tipo = panar ? 'pan' : (this.modo === 'edicao' && p && !p.fixo ? 'peca' : 'talvez-pan');
                    this.gesto = { tipo, x0: e.clientX, y0: e.clientY, x: e.clientX, y: e.clientY, cel, moveu: false, longo: false };
                    if (e.pointerType !== 'mouse') {
                        this.gesto.timer = setTimeout(() => {
                            if (this.gesto && !this.gesto.moveu && this.pecaEm(cel.x, cel.y)) {
                                this.gesto.longo = true;
                                this.selecionada = { x: cel.x, y: cel.y };
                                if (navigator.vibrate) navigator.vibrate(15);
                                this.desenharMarcas();
                            }
                        }, 450);
                    }
                },
                aoMover(e) {
                    if (e.pointerType === 'mouse' && !this.ponteiros.size) {
                        const cel = this.celulaEm(e.clientX, e.clientY);
                        if (!this.hover || this.hover.x !== cel.x || this.hover.y !== cel.y) { this.hover = cel; this.desenharMarcas(); }
                        return;
                    }
                    if (!this.ponteiros.has(e.pointerId)) return;
                    this.ponteiros.set(e.pointerId, { x: e.clientX, y: e.clientY });
                    const g = this.gesto;
                    if (!g) return;
                    if (g.tipo === 'pinca') {
                        if (this.ponteiros.size < 2) return;
                        const [a, b] = [...this.ponteiros.values()];
                        const dist = Math.hypot(a.x - b.x, a.y - b.y);
                        const meio = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
                        if (dist > 0 && g.dist > 0) this.zoom(g.dist / dist, meio.x, meio.y);
                        const k = this.escalaTela();
                        this.cam.x -= (meio.x - g.meio.x) * k;
                        this.cam.y -= (meio.y - g.meio.y) * k;
                        this.aplicarCamera();
                        g.dist = dist; g.meio = meio;
                        return;
                    }
                    if (!g.moveu && Math.hypot(e.clientX - g.x0, e.clientY - g.y0) > 8) {
                        g.moveu = true;
                        clearTimeout(g.timer);
                        if (g.tipo === 'peca') { this.arrasto = { de: g.cel, para: g.cel }; }
                        if (g.tipo === 'talvez-pan') g.tipo = 'pan';
                        if (g.tipo === 'pan') dom.svg.classList.add('panando');
                    }
                    if (!g.moveu) return;
                    if (g.tipo === 'pan') {
                        const k = this.escalaTela();
                        this.cam.x -= (e.clientX - g.x) * k;
                        this.cam.y -= (e.clientY - g.y) * k;
                        this.aplicarCamera();
                    } else if (this.arrasto) {
                        const cel = this.celulaEm(e.clientX, e.clientY);
                        if (cel.x !== this.arrasto.para.x || cel.y !== this.arrasto.para.y) { this.arrasto.para = cel; this.desenharMarcas(); }
                    }
                    g.x = e.clientX; g.y = e.clientY;
                },
                aoSoltar(e) {
                    if (!this.ponteiros.has(e.pointerId)) return;
                    this.ponteiros.delete(e.pointerId);
                    const g = this.gesto;
                    dom.svg.classList.remove('panando');
                    if (!g) return;
                    if (g.tipo === 'pinca') {
                        if (this.ponteiros.size === 0) this.gesto = null;
                        else {
                            const [a] = [...this.ponteiros.values()];
                            this.gesto = { tipo: 'pan', x0: a.x, y0: a.y, x: a.x, y: a.y, moveu: true };
                        }
                        return;
                    }
                    clearTimeout(g.timer);
                    this.gesto = null;
                    if (e.type === 'pointercancel') { this.arrasto = null; this.desenharMarcas(); return; }
                    if (this.arrasto) {
                        const { de, para } = this.arrasto;
                        this.arrasto = null;
                        this.moverPeca(de, para);
                        return;
                    }
                    if (!g.moveu && !g.longo) this.tocar(g.cel);
                },
                aoRolar(e) {
                    this.zoom(Math.exp(e.deltaY * (e.deltaMode === 1 ? 0.05 : 0.0016)), e.clientX, e.clientY);
                },
                moverPeca(de, para) {
                    const p = this.montagem.find(q => q.x === de.x && q.y === de.y);
                    if (!p || (de.x === para.x && de.y === para.y)) { this.desenharMarcas(); return; }
                    const outras = this.pecasNoTabuleiro().filter(q => q !== p);
                    if (!podeConstruir(this.fase, outras, para.x, para.y)) { this.som('erro'); this.desenharMarcas(); return; }
                    this.lembrar();
                    p.x = para.x; p.y = para.y;
                    this.selecionada = { x: para.x, y: para.y };
                    this.som('instalar');
                    this.mudou();
                },
                tocar(cel) {
                    if (this.modo !== 'edicao') return;
                    const p = this.pecaEm(cel.x, cel.y);
                    if (p) {
                        this.selecionada = { x: cel.x, y: cel.y };
                        this.som('girar');
                        this.desenharMarcas();
                        return;
                    }
                    if (this.ferramenta) {
                        if (!this.instalar(cel.x, cel.y)) { this.som('erro'); this.desenharMarcas(); }
                        return;
                    }
                    this.selecionada = null;
                    this.desenharMarcas();
                },

                // ----- Teclado (opcional; tudo também funciona por toque) -----
                tecla(e) {
                    if (this.tela !== 'jogo' || e.target.closest('input, textarea')) return;
                    if (e.key === ' ') { this.espaco = true; e.preventDefault(); return; }
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') { this.desfazer(); e.preventDefault(); return; }
                    const k = e.key.toLowerCase();
                    if (k === 'r') this.girar();
                    else if (k === 'f') this.espelhar();
                    else if (k === 'delete' || k === 'backspace') this.remover();
                    else if (k === 'enter') this.modo === 'edicao' ? this.executar() : this.alternarPausa();
                    else if (k === 'escape') { this.ferramenta = null; this.selecionada = null; this.desenharMarcas(); }
                    else if (k === 'c') this.enquadrar();
                    else if (k === 'i' && this.pecaSelecionada) this.infoPeca = !this.infoPeca;
                    else if (/^[1-4]$/.test(k)) { const t = this.tiposDaFase()[+k - 1]; if (t) this.escolherFerramenta(t); }
                },
                teclaSolta(e) { if (e.key === ' ') this.espaco = false; },

                // ----- Desenho -----
                redesenharTudo() {
                    this.cacheCena = null;
                    this.avisos = avisosDeConexao(this.fase, this.montagem);
                    this.desenharChao();
                    this.desenharCena();
                    this.desenharMarcas();
                },
                desenharChao() {
                    const f = this.fase;
                    const W = f.largura, H = f.altura;
                    let s = caixa(0, 0, W, H, -22, 0, { topo: '#cfc6b8', dir: '#8c8173', esq: '#6f665b' });
                    for (let y = 0; y < H; y++) {
                        for (let x = 0; x < W; x++) {
                            s += poli([[x, y, 0], [x + 1, y, 0], [x + 1, y + 1, 0], [x, y + 1, 0]], (x + y) % 2 ? '#cbc1b2' : '#d4cbbd', 'stroke="#b2a796" stroke-width=".8"');
                        }
                    }
                    f.coletores.filter(c => c.tipo === 'fosso').forEach(c => { s += desenharFosso(c); });
                    dom.chao.innerHTML = s;
                },
                /** Itens altos por célula (peças, coletores, obstáculos); ficam em cache e são ordenados com os pacotes. */
                itensFixos() {
                    if (this.cacheCena) return this.cacheCena;
                    const f = this.fase;
                    const itens = [];
                    (f.obstaculos || []).forEach(([x, y], i) => itens.push({ d: x + y, s: desenharObstaculo(x, y, this.idx * 5 + i) }));
                    itens.push({ d: f.entrada.x + f.entrada.y, s: desenharEntrada(f.entrada) });
                    const fossoEm = (x, y) => { const c = coletorEm(f, x, y); return c && c.tipo === 'fosso' ? c.id : null; };
                    this.pecasNoTabuleiro().forEach(p => {
                        if (p.tipo === 'desviador') return;
                        const partes = desenharPartes(p, null, fossoEm(p.x, p.y));
                        itens.push({ d: p.x + p.y, k: chave(p.x, p.y), s: partes.fundo });
                        if (partes.frente) itens.push({ d: p.x + p.y + 0.7, k: chave(p.x, p.y), s: partes.frente });
                    });
                    f.coletores.filter(c => c.tipo === 'fosso').forEach(c => {
                        const cobertura = this.pecaEm(c.x, c.y);
                        if (cobertura && cobertura.tipo === 'separador') return; // o separador já mostra o coletor na própria etiqueta
                        // Fosso livre: etiqueta no centro do buraco. Coberto por outra peça: etiqueta flutuando acima dela.
                        const z = cobertura ? ALTURA_ESTEIRA + 26 : 4;
                        itens.push({ d: cobertura ? c.x + c.y + 0.6 : 1e6, s: etiqueta(c.x + 0.5, c.y + 0.5, z, SIMBOLO_COLETOR[c.id] + ' ' + c.id, COR_COLETOR[c.id]) });
                    });
                    this.cacheCena = itens;
                    return itens;
                },
                desenharCena() {
                    const f = this.fase;
                    const itens = this.itensFixos().slice();
                    const fr = this.sim ? this.sim.frames : null;
                    const t = this.sim ? (this.salvo.prefs.reduzir ? Math.floor(this.tick) : this.tick) : 0;
                    const i = Math.floor(t);
                    const a = t - i;
                    const f0 = fr ? fr[Math.min(i, fr.length - 1)] : null;
                    const f1 = fr ? fr[Math.min(i + 1, fr.length - 1)] : null;

                    const contagemColetor = {};
                    if (f0) f0.pacotes.forEach(p => { if ((p.estado === 'entregue' || p.estado === 'caiu') && p.coletor) contagemColetor[p.coletor] = (contagemColetor[p.coletor] || 0) + 1; });
                    f.coletores.filter(c => c.tipo === 'final').forEach(c => {
                        const partes = desenharColetorFinal(c, contagemColetor[c.id] || 0);
                        itens.push({ d: c.x + c.y, s: partes.fundo }, { d: c.x + c.y + 0.7, s: partes.frente });
                    });
                    this.pecasNoTabuleiro().filter(p => p.tipo === 'desviador').forEach(p => {
                        const n = f0 ? (f0.desvio[chave(p.x, p.y)] || 0) : 0;
                        const partes = desenharPartes(p, saidaDoDesviador(p, n));
                        itens.push({ d: p.x + p.y, k: chave(p.x, p.y), s: partes.fundo });
                        if (partes.frente) itens.push({ d: p.x + p.y + 0.7, k: chave(p.x, p.y), s: partes.frente });
                    });
                    const ent = f.entrada;
                    const dEnt = ent.x + ent.y;

                    if (fr) {
                        const falha = this.sim.falha;
                        const zb = ALTURA_ESTEIRA;
                        f0.pacotes.forEach((s0, id) => {
                            const s1 = f1.pacotes[id];
                            const dados = f.pacotes[id];
                            if (s1.estado === 'fila') return;
                            if (s0.estado === 'entregue' || s0.estado === 'caiu') return;
                            let fx, fy, z = zb, op = 1;
                            const destaque = s1.estado === 'falhou' && !!this.resultado && falha && falha.pacote === id;
                            // Acabou de sair na fila: ainda está dentro da máquina de entrada, invisível.
                            if (s0.estado === 'fila') return;
                            {
                                fx = s0.x + 0.5 + (s1.x - s0.x) * a;
                                fy = s0.y + 0.5 + (s1.y - s0.y) * a;
                                if (s0.estado === 'caindo' && s1.estado === 'caiu') { z = zb - a * a * (zb + 30); op = 1 - a; }
                                else if (s0.estado === 'caindo' && s1.estado === 'falhou') { z = zb - a * zb; }
                                else if (s1.estado === 'entregue' && s0.estado !== 'entregue') { z = zb + Math.sin(a * Math.PI) * 22 - a * 16; op = a < 0.85 ? 1 : 1 - (a - 0.85) / 0.15; }
                                else if (s1.estado === 'falhou' && falha) {
                                    if (falha.motivo === 'saiu') { const q = Math.max(0, a - 0.5) * 2; z = zb - q * q * 50; op = 1 - q * 0.8; }
                                    else if (falha.motivo === 'sem_esteira') { z = zb - Math.max(0, a - 0.5) * 2 * zb; }
                                    else if (['obstaculo', 'conexao', 'colisao'].includes(falha.motivo) && s0.x === s1.x && s0.y === s1.y) {
                                        fx += Math.sin(a * Math.PI * 6) * 0.05 * (1 - a);
                                    }
                                    if (s0.estado === 'falhou') {
                                        if (falha.motivo === 'saiu') return;
                                        if (falha.motivo === 'sem_esteira' || falha.motivo === 'chao') z = 0;
                                    }
                                }
                            }
                            // Ordem de desenho pela célula onde está o centro do pacote (+0.5: depois do fundo da peça, antes da frente).
                            let d = Math.floor(fx) + Math.floor(fy) + 0.5;
                            if (s1.estado === 'entregue' && s0.estado !== 'entregue') d = s1.x + s1.y + 0.5; // pulando para dentro do coletor
                            let corte = null;
                            if (s0.x === ent.x && s0.y === ent.y && s0.estado === 'esteira') {
                                // Saindo da entrada: só a parte que já atravessou a face da máquina existe. Pela frente,
                                // fica sobre a primeira esteira; por trás, a máquina (desenhada depois) cobre o resto.
                                corte = [{ xmin: ent.x + 0.95 }, { ymin: ent.y + 0.95 }, { xmax: ent.x + 0.05 }, { ymax: ent.y + 0.05 }][ent.dir];
                                d = ent.dir <= 1 ? dEnt + 1.5 : dEnt - 0.4;
                            }
                            let svgPacote = desenharPacote(dados, fx, fy, z, Math.max(0, op), destaque, corte);
                            if (corte && ent.dir <= 1) {
                                // A parte que ainda está no túnel aparece só pela boca da abertura (clipPath no contorno dela).
                                const z0 = ALTURA_ESTEIRA, z1 = ALTURA_ESTEIRA + 32;
                                const boca = ent.dir === 0
                                    ? [[ent.x + 0.95, ent.y + 0.25, z0], [ent.x + 0.95, ent.y + 0.75, z0], [ent.x + 0.95, ent.y + 0.75, z1], [ent.x + 0.95, ent.y + 0.25, z1]]
                                    : [[ent.x + 0.25, ent.y + 0.95, z0], [ent.x + 0.75, ent.y + 0.95, z0], [ent.x + 0.75, ent.y + 0.95, z1], [ent.x + 0.25, ent.y + 0.95, z1]];
                                const dentroDoTunel = ent.dir === 0 ? { xmax: ent.x + 0.95 } : { ymax: ent.y + 0.95 };
                                const idBoca = 'boca-' + id;
                                svgPacote = `<clipPath id="${idBoca}"><polygon points="${pts(boca)}"/></clipPath><g clip-path="url(#${idBoca})">`
                                    + desenharPacote(dados, fx, fy, z, Math.max(0, op), false, dentroDoTunel) + '</g>' + svgPacote;
                            }
                            itens.push({ d, s: svgPacote });
                        });
                    }
                    itens.sort((p, q) => p.d - q.d);
                    // Peça selecionada: brilho amarelo em volta (filtro #brilho), no lugar de um contorno que cruzaria a máquina.
                    const sel = this.modo === 'edicao' && this.selecionada ? chave(this.selecionada.x, this.selecionada.y) : null;
                    this.selDesenhada = sel;
                    if (sel) itens.forEach((x, n) => { if (x.k === sel) itens[n] = { d: x.d, s: `<g filter="url(#brilho)">${x.s}</g>` }; });
                    dom.cena.innerHTML = itens.map(x => x.s).join('');
                },
                desenharMarcas() {
                    if (this.tela !== 'jogo') return;
                    const sel = this.modo === 'edicao' && this.selecionada ? chave(this.selecionada.x, this.selecionada.y) : null;
                    if (sel !== this.selDesenhada) this.desenharCena();
                    const f = this.fase;
                    let s = '';
                    // Contorno no topo da peça quando há uma (senão flutua no meio dela); no chão quando a célula está vazia.
                    const losango = (x, y, cor, extra, z) => {
                        const h = z !== undefined ? z : (this.pecaEm(x, y) ? ALTURA_ESTEIRA + 0.5 : 1);
                        return poli([[x + 0.03, y + 0.03, h], [x + 0.97, y + 0.03, h], [x + 0.97, y + 0.97, h], [x + 0.03, y + 0.97, h]], 'none', `stroke="${cor}" stroke-width="3" stroke-linejoin="round" ${extra || ''}`);
                    };
                    if (this.modo === 'edicao') {
                        this.avisos.forEach(av => {
                            const cor = av.tipo === 'erro' ? '#ef4b3c' : '#f1b437';
                            const ox = av.dir === null ? 0.5 : 0.5 + DIRS[av.dir][0] * 0.48;
                            const oy = av.dir === null ? 0.5 : 0.5 + DIRS[av.dir][1] * 0.48;
                            const p = proj(av.x + ox, av.y + oy, av.dir === null ? ALTURA_ESTEIRA + 24 : ALTURA_ESTEIRA + 8);
                            s += `<g transform="translate(${p[0].toFixed(1)},${p[1].toFixed(1)})"><title>${av.texto}</title><circle r="8" fill="${cor}" stroke="#1b232d" stroke-width="2"/><text text-anchor="middle" dominant-baseline="central" font-size="11" font-weight="900" fill="#1b232d">!</text></g>`;
                        });
                        if (this.arrasto) {
                            const { de, para } = this.arrasto;
                            const p = this.montagem.find(q => q.x === de.x && q.y === de.y);
                            const ok = p && podeConstruir(f, this.pecasNoTabuleiro().filter(q => q !== p), para.x, para.y);
                            if (dentro(f, para.x, para.y) || ok) s += losango(para.x, para.y, ok ? '#5ee06e' : '#ef4b3c', '', ok ? ALTURA_ESTEIRA + 0.5 : undefined);
                            if (p && ok) s += `<g opacity=".6">${desenharPeca(Object.assign({}, p, { x: para.x, y: para.y }))}</g>`;
                        } else if (this.ferramenta && this.hover && dentro(f, this.hover.x, this.hover.y)) {
                            const { x, y } = this.hover;
                            const ok = podeConstruir(f, this.pecasNoTabuleiro(), x, y);
                            if (ok) {
                                const fantasma = { tipo: this.ferramenta, x, y, dir: this.orientacao.dir, lado: this.orientacao.lado, abertura: this.limite('abertura')[0], grupo: 1, primeiro: 'frente' };
                                s += `<g opacity=".55">${desenharPeca(fantasma)}</g>`;
                            } else if (!this.pecaEm(x, y)) s += losango(x, y, '#ef4b3c');
                        }
                    }
                    if (this.resultado && !this.resultado.vitoria && this.sim.falha) {
                        const { x, y } = this.sim.falha;
                        s += losango(x, y, '#ef4b3c', 'class="pulsar"');
                    }
                    dom.marcas.innerHTML = s;
                },

                // ----- Som (WebAudio, curto, opcional) -----
                som(tipo) {
                    if (!this.salvo.prefs.som) return;
                    try {
                        this.audio = this.audio || new (window.AudioContext || window.webkitAudioContext)();
                        const ctx = this.audio;
                        const notas = {
                            instalar: [['square', 520, 0.05, 0.05]], girar: [['triangle', 760, 0.04, 0.05]], remover: [['square', 300, 0.06, 0.05]],
                            erro: [['square', 160, 0.12, 0.06]], ligar: [['sawtooth', 110, 0.28, 0.05, 260]], saida: [['triangle', 420, 0.04, 0.03]],
                            separa: [['square', 340, 0.05, 0.04]], queda: [['sine', 240, 0.2, 0.08, 80]], desvio: [['square', 620, 0.04, 0.04]],
                            entrega: [['sine', 880, 0.12, 0.07]], falha: [['square', 150, 0.4, 0.06, 90]],
                            vitoria: [['triangle', 523, 0.12, 0.07], ['triangle', 659, 0.12, 0.07, null, 0.12], ['triangle', 784, 0.12, 0.07, null, 0.24], ['triangle', 1046, 0.25, 0.07, null, 0.36]],
                        }[tipo];
                        if (!notas) return;
                        notas.forEach(([forma, freq, dur, vol, ate, atraso]) => {
                            const t0 = ctx.currentTime + (atraso || 0);
                            const o = ctx.createOscillator();
                            const g = ctx.createGain();
                            o.type = forma;
                            o.frequency.setValueAtTime(freq, t0);
                            if (ate) o.frequency.exponentialRampToValueAtTime(ate, t0 + dur);
                            g.gain.setValueAtTime(vol, t0);
                            g.gain.exponentialRampToValueAtTime(0.001, t0 + dur);
                            o.connect(g).connect(ctx.destination);
                            o.start(t0);
                            o.stop(t0 + dur + 0.02);
                        });
                    } catch (e) { /* sem áudio */ }
                },
                alternarPref(nome) {
                    this.salvo.prefs[nome] = !this.salvo.prefs[nome];
                    this.salvar();
                },
            };
        }
    </script>
    @endverbatim

    <div class="app" x-data="engenhocopolis()" x-init="iniciar()" @keydown.window="tecla($event)" @keyup.window="teclaSolta($event)">

        {{-- ================= MAPA ================= --}}
        <section class="mapa" x-show="tela === 'mapa'">
            <div class="mapa-int">
                <h1 class="logo">
                    <span>ENGENHOC</span>
                    <svg viewBox="-12 -12 24 24" aria-hidden="true"><g fill="#4f8fe0" stroke="#1d3a63" stroke-width="1"><path d="M-1.6-11h3.2l.6 3a8 8 0 0 1 2.4 1l2.6-1.7 2.3 2.3-1.7 2.6a8 8 0 0 1 1 2.4l3 .6v3.2l-3 .6a8 8 0 0 1-1 2.4l1.7 2.6-2.3 2.3-2.6-1.7a8 8 0 0 1-2.4 1l-.6 3h-3.2l-.6-3a8 8 0 0 1-2.4-1l-2.6 1.7-2.3-2.3 1.7-2.6a8 8 0 0 1-1-2.4l-3-.6v-3.2l3-.6a8 8 0 0 1 1-2.4l-1.7-2.6 2.3-2.3 2.6 1.7a8 8 0 0 1 2.4-1z"/><circle r="3.6" fill="#1d3a63"/></g></svg>
                    <span>POLIS</span>
                </h1>
                <p class="subtitulo">Monte a linha, aperte EXECUTAR e veja cada pacote chegar ao coletor certo.</p>

                <template x-for="(nomeSetor, s) in SETORES" :key="s">
                    <div class="setor">
                        <h2 x-text="nomeSetor"></h2>
                        <div class="grade-fases">
                            <template x-for="n in setorDe(s)" :key="n">
                                <button type="button" class="cartao-fase" :class="{ atual: n + 1 === salvo.desbloqueada && !medalhas(n).concluida }" :disabled="!liberada(n)" @click="abrirFase(n)">
                                    <span class="num-fase" x-text="liberada(n) ? n + 1 : '🔒'"></span>
                                    <span class="nome-fase" x-text="FASES[n].nome"></span>
                                    <span class="medalhas">
                                        <span class="medalha" :class="{ on: medalhas(n).concluida }" title="Fase concluída">✔</span>
                                        <span class="medalha" :class="{ on: medalhas(n).economica }" title="Econômica: no máximo as peças da solução de referência">⚙</span>
                                        <span class="medalha" :class="{ on: medalhas(n).primeira }" title="De primeira: concluída sem nenhuma execução com falha">1ª</span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="caixa-info">
                    <b>Como jogar.</b> Escolha uma peça no inventário e toque numa célula livre para instalá-la. Toque numa peça para selecioná-la (gire, espelhe, ajuste ou remova) e arraste para mudá-la de lugar. Pinça ou roda do mouse dão zoom; arraste o fundo para mover a câmera. Medalhas: <b>✔</b> concluída, <b>⚙</b> econômica (peças da solução de referência) e <b>1ª</b> de primeira.
                </div>
                <div class="linha-prefs">
                    <button type="button" class="bt" @click="alternarPref('som')" x-text="salvo.prefs.som ? '🔊 Som ligado' : '🔇 Som desligado'"></button>
                    <button type="button" class="bt" @click="alternarPref('reduzir')" x-text="salvo.prefs.reduzir ? 'Animações reduzidas' : 'Animações completas'"></button>
                    <a href="/" class="bt" style="text-decoration:none">Portal</a>
                </div>
            </div>
        </section>

        {{-- ================= JOGO ================= --}}
        <section class="jogo" x-show="tela === 'jogo'" x-cloak>
            <header class="topo">
                <button type="button" class="bt bt-icone" @click="voltarAoMapa()" aria-label="Voltar ao mapa" title="Mapa">☰</button>
                <div class="topo-titulo">
                    <b x-text="'Fase ' + (idx + 1) + ' · ' + fase.nome"></b>
                    <span x-text="fase.objetivo"></span>
                </div>
                <span class="progresso" :title="'Entregues / total'" x-text="(modo === 'execucao' ? entreguesAgora : 0) + '/' + fase.pacotes.length"></span>
                <button type="button" class="bt bt-icone" @click="ajuda = true" aria-label="Ajuda" title="Ajuda">?</button>
            </header>

            <div class="palco">
                <svg x-ref="svg" class="tabuleiro" preserveAspectRatio="xMidYMid meet" font-family="system-ui, sans-serif"
                     @pointerdown="aoApertar($event)" @pointermove="aoMover($event)" @pointerup="aoSoltar($event)" @pointercancel="aoSoltar($event)"
                     @pointerleave="if ($event.pointerType === 'mouse' && hover) { hover = null; desenharMarcas(); }"
                     @wheel.prevent="aoRolar($event)" @contextmenu.prevent role="img" :aria-label="'Tabuleiro da fase ' + (idx + 1)">
                    <defs>
                        <filter id="brilho" x="-40%" y="-40%" width="180%" height="180%">
                            <feMorphology in="SourceAlpha" operator="dilate" radius="2.5" result="borda"/>
                            <feGaussianBlur in="borda" stdDeviation="3.5" result="difuso"/>
                            <feFlood flood-color="#ffd84a"/>
                            <feComposite in2="difuso" operator="in" result="halo"/>
                            <feMerge><feMergeNode in="halo"/><feMergeNode in="halo"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <g x-ref="chao"></g>
                    <g x-ref="cena"></g>
                    <g x-ref="marcas"></g>
                </svg>

                <div class="camera">
                    <button type="button" class="bt bt-icone" @click="zoomBotao(0.8)" aria-label="Aproximar" title="Aproximar">＋</button>
                    <button type="button" class="bt bt-icone" @click="zoomBotao(1.25)" aria-label="Afastar" title="Afastar">－</button>
                    <button type="button" class="bt bt-icone" @click="enquadrar()" aria-label="Enquadrar fábrica" title="Enquadrar fábrica (C)">⛶</button>
                </div>

                <div class="cartao intro" x-show="mostrarIntro && modo === 'edicao'" x-cloak>
                    <h3 x-text="fase.nome"></h3>
                    <p x-text="fase.intro"></p>
                    <button type="button" class="bt bt-primario" @click="fecharIntro()">Entendi</button>
                </div>

                <div class="inspetor" x-show="modo === 'edicao' && pecaSelecionada" x-cloak>
                    <template x-if="pecaSelecionada">
                        <div class="inspetor-int">
                            {{-- Explicação completa, aberta pelo botão de informações. --}}
                            <div class="cartao info-peca" x-show="infoPeca" x-cloak>
                                <h3 x-text="NOMES_PECA[pecaSelecionada.tipo]"></h3>
                                <template x-for="(paragrafo, n) in INFO_PECA[pecaSelecionada.tipo]" :key="n">
                                    <p x-text="paragrafo"></p>
                                </template>
                                <p class="info-atual" x-show="pecaSelecionada.tipo === 'separador'" x-text="'Agora: abertura ' + pecaSelecionada.abertura + '. ' + descreverAbertura(pecaSelecionada.abertura)"></p>
                                <p class="info-atual" x-show="pecaSelecionada.tipo === 'desviador'" x-text="'Agora: ' + descreverDesviador(pecaSelecionada)"></p>
                                <p class="info-atual" x-show="pecaSelecionada.fixo">Esta peça é fixa: faz parte da fábrica e não pode ser girada, movida nem removida.</p>
                            </div>

                            <div class="barra-peca" role="toolbar" :aria-label="NOMES_PECA[pecaSelecionada.tipo]">
                                <template x-if="pecaSelecionada.tipo === 'separador'">
                                    <div class="grupo-param">
                                        <button type="button" class="bi" :disabled="pecaSelecionada.fixo || pecaSelecionada.abertura <= limite('abertura')[0]" @click="ajustar('abertura', -1)" aria-label="Diminuir abertura" title="Diminuir abertura" x-html="ICONES.menos"></button>
                                        <div class="vao" :title="'Abertura ' + pecaSelecionada.abertura + ': ' + descreverAbertura(pecaSelecionada.abertura)" role="img" :aria-label="'Abertura ' + pecaSelecionada.abertura + '. ' + descreverAbertura(pecaSelecionada.abertura)">
                                            <template x-for="l in [1, 2, 3]" :key="l">
                                                <i :class="{ cai: l < pecaSelecionada.abertura }" :style="{ width: (12 + l * 6) + 'px', height: (12 + l * 6) + 'px' }" x-text="LETRAS[l]"></i>
                                            </template>
                                        </div>
                                        <button type="button" class="bi" :disabled="pecaSelecionada.fixo || pecaSelecionada.abertura >= limite('abertura')[1]" @click="ajustar('abertura', 1)" aria-label="Aumentar abertura" title="Aumentar abertura" x-html="ICONES.mais"></button>
                                    </div>
                                </template>
                                <template x-if="pecaSelecionada.tipo === 'desviador'">
                                    <div class="grupo-param">
                                        <button type="button" class="bi" :class="{ on: pecaSelecionada.primeiro !== 'lado' }" :disabled="!!pecaSelecionada.fixo" @click="definir('primeiro', 'frente')" aria-label="Começa pela frente" title="Começa pela frente" x-html="ICONES.frente"></button>
                                        <button type="button" class="bi" :class="{ on: pecaSelecionada.primeiro === 'lado' }" :disabled="!!pecaSelecionada.fixo" @click="definir('primeiro', 'lado')" aria-label="Começa pelo lado" title="Começa pelo lado" x-html="pecaSelecionada.lado === 'E' ? ICONES.ladoE : ICONES.ladoD"></button>
                                        <button type="button" class="bi" :disabled="pecaSelecionada.fixo || pecaSelecionada.grupo <= limite('grupo')[0]" @click="ajustar('grupo', -1)" aria-label="Diminuir grupo" title="Diminuir grupo" x-html="ICONES.menos"></button>
                                        <span class="valor" :title="'Grupos de ' + pecaSelecionada.grupo" x-text="'×' + pecaSelecionada.grupo"></span>
                                        <button type="button" class="bi" :disabled="pecaSelecionada.fixo || pecaSelecionada.grupo >= limite('grupo')[1]" @click="ajustar('grupo', 1)" aria-label="Aumentar grupo" title="Aumentar grupo" x-html="ICONES.mais"></button>
                                    </div>
                                </template>
                                <template x-if="!pecaSelecionada.fixo">
                                    <div class="grupo-acoes">
                                        <button type="button" class="bi" @click="girar()" aria-label="Girar" title="Girar (R)" x-html="ICONES.girar"></button>
                                        <button type="button" class="bi" x-show="podeEspelhar()" @click="espelhar()" aria-label="Espelhar" title="Espelhar (F)" x-html="ICONES.espelhar"></button>
                                        <button type="button" class="bi bi-perigo" @click="remover()" aria-label="Remover" title="Remover (Delete)" x-html="ICONES.remover"></button>
                                    </div>
                                </template>
                                <span class="bi bi-fixa" x-show="pecaSelecionada.fixo" role="img" aria-label="Peça fixa" title="Peça fixa" x-html="ICONES.cadeado"></span>
                                <button type="button" class="bi" :class="{ on: infoPeca }" @click="infoPeca = !infoPeca" aria-label="Informações da peça" title="Informações (I)" x-html="ICONES.info"></button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="cartao resultado" :class="resultado && resultado.vitoria ? 'ok' : 'erro'" x-show="resultado" x-cloak>
                    <template x-if="resultado && resultado.vitoria">
                        <div>
                            <h3>✔ Linha concluída!</h3>
                            <p x-text="'Todos os ' + fase.pacotes.length + ' pacotes chegaram ao destino.'"></p>
                            <div class="medalhas" style="margin-bottom:10px">
                                <span class="medalha on" title="Concluída">✔</span>
                                <span class="medalha" :class="{ on: resultado.medalhas.economica }" title="Econômica">⚙</span>
                                <span class="medalha" :class="{ on: resultado.medalhas.primeira }" title="De primeira">1ª</span>
                                <span style="font-size:12.5px;color:var(--texto-2);align-self:center" x-text="montagem.length + ' peça(s); referência: ' + fase.referencia.length"></span>
                            </div>
                            <div class="acoes">
                                <button type="button" class="bt bt-primario" @click="proximaFase()" x-text="idx + 1 < FASES.length ? 'Próxima fase' : 'Voltar ao mapa'"></button>
                                <button type="button" class="bt" @click="repetir()">Ver de novo</button>
                                <button type="button" class="bt" @click="editar()">Editar</button>
                            </div>
                        </div>
                    </template>
                    <template x-if="resultado && !resultado.vitoria">
                        <div>
                            <h3>✖ A linha falhou</h3>
                            <p x-text="resultado.falha.mensagem"></p>
                            <p x-show="resultado.falha.dica" x-text="'Dica: ' + resultado.falha.dica"></p>
                            <div class="acoes">
                                <button type="button" class="bt bt-primario" @click="editar()">Voltar a editar</button>
                                <button type="button" class="bt" @click="repetir()">Ver de novo</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <footer class="rodape">
                <div class="fila" aria-label="Fila de pacotes">
                    <span class="fila-rotulo">Fila</span>
                    <template x-for="(p, i) in fase.pacotes" :key="i">
                        <span class="pac" :class="estadoNaFila(i)" :title="'Pacote nº ' + (i + 1) + ': ' + TAMANHOS[p.largura] + ', destino ' + p.destino">
                            <i :style="{ background: COR_COLETOR[p.destino], width: (12 + p.largura * 4) + 'px', height: (12 + p.largura * 4) + 'px' }" x-text="LETRAS[p.largura]"></i>
                            <span x-text="'→ ' + SIMBOLO_COLETOR[p.destino] + ' ' + p.destino" :style="{ color: COR_COLETOR[p.destino] }"></span>
                        </span>
                    </template>
                </div>
                <div class="ferramentas">
                    <div class="inventario" x-show="modo === 'edicao'">
                        <template x-for="t in tiposDaFase()" :key="t">
                            <button type="button" class="peca-bt" :class="{ on: ferramenta === t }" :disabled="restante(t) <= 0 && ferramenta !== t" @click="escolherFerramenta(t)" :title="NOMES_PECA[t]" :aria-label="NOMES_PECA[t] + ', restam ' + restante(t)">
                                <span x-html="icones[t]"></span>
                                <span class="qtd" x-text="restante(t)"></span>
                            </button>
                        </template>
                    </div>
                    <div class="comandos" x-show="modo === 'edicao'">
                        <button type="button" class="bt" @click="girar()" :disabled="!ferramenta && !(pecaSelecionada && !pecaSelecionada.fixo)" title="Girar (R)">⟳</button>
                        <button type="button" class="bt" @click="espelhar()" :disabled="!podeEspelhar()" title="Espelhar (F)">⇋</button>
                        <button type="button" class="bt" @click="desfazer()" :disabled="!historico.length" title="Desfazer (Ctrl+Z)">↶</button>
                        <button type="button" class="bt" @click="limparTudo()" :disabled="!montagem.length" title="Recolher todas as peças">Limpar</button>
                        <button type="button" class="bt bt-verde" @click="executar()" title="Executar (Enter)">▶ EXECUTAR</button>
                    </div>
                    <div class="comandos" x-show="modo === 'execucao'" x-cloak>
                        <button type="button" class="bt" @click="alternarPausa()" :disabled="!!resultado" x-text="rodando ? '❚❚ Pausar' : '▶ Continuar'"></button>
                        <button type="button" class="bt" @click="passo()" :disabled="!!resultado" title="Avançar um passo">⏭ Passo</button>
                        <button type="button" class="bt" @click="trocarVelocidade()" x-text="(salvo.prefs.velocidade || 1) + '×'" title="Velocidade"></button>
                        <button type="button" class="bt" @click="repetir()" title="Reiniciar a simulação">↺ Reiniciar</button>
                        <button type="button" class="bt bt-primario" @click="editar()">✎ Editar</button>
                    </div>
                </div>
            </footer>
        </section>

        <div class="ajuda" x-show="ajuda" x-cloak @click.self="ajuda = false">
            <div class="cartao">
                <h3>Como funciona a fábrica</h3>
                <ul>
                    <li>Os pacotes saem da <b>entrada</b> em ordem, um a cada dois passos. Cada pacote tem a cor e o símbolo do coletor para onde deve ir; a letra em cima é o tamanho (P, M, G).</li>
                    <li><b>Esteiras</b> levam o pacote no sentido da seta; ele precisa chegar pela traseira da peça.</li>
                    <li><b>Separador com vão</b>: largura menor que a abertura cai no fosso embaixo; largura igual ou maior segue. Fora de um fosso, o pacote cai no chão.</li>
                    <li><b>Desviador</b>: alterna grupos de pacotes entre a saída da frente e a lateral.</li>
                    <li>Alertas <span style="color:#ef4b3c">vermelhos</span> são encaixes impossíveis; <span style="color:#f1b437">amarelos</span>, caminhos ainda abertos. Você pode executar mesmo assim.</li>
                    <li>A mesma montagem sempre dá o mesmo resultado. Sua montagem fica salva neste aparelho.</li>
                </ul>
                <p>Atalhos: 1–4 peças, R girar, F espelhar, Delete remover, Ctrl+Z desfazer, Enter executar/pausar, C enquadrar, espaço + arrastar move a câmera.</p>
                <div class="acoes">
                    <button type="button" class="bt" @click="alternarPref('som')" x-text="salvo.prefs.som ? '🔊 Som ligado' : '🔇 Som desligado'"></button>
                    <button type="button" class="bt" @click="alternarPref('reduzir')" x-text="salvo.prefs.reduzir ? 'Animações reduzidas' : 'Animações completas'"></button>
                    <button type="button" class="bt bt-primario" @click="ajuda = false">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</x-Engenhocopolis::layout>
