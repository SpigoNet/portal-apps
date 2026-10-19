// Verifica as fases publicadas do Engenhocópolis usando a lógica pura do index.blade.php.
// Uso: node app/Modules/Engenhocopolis/ferramentas/verificar-fases.mjs  (sai com código 1 se algo falhar)
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));
const fonte = readFileSync(join(aqui, '../resources/views/index.blade.php'), 'utf8');
const inicio = fonte.indexOf('// ENGENHO-LOGICA-INICIO');
const fim = fonte.indexOf('// ENGENHO-LOGICA-FIM');
if (inicio < 0 || fim < 0) throw new Error('Marcadores da lógica não encontrados.');

const L = new Function(fonte.slice(inicio, fim) + '\nreturn { FASES, simular, avisosDeConexao, contarPecas, podeConstruir, todasAsPecas, saidaDoDesviador };')();

const erros = [];
const checar = (cond, msg) => { if (!cond) erros.push(msg); };

L.FASES.forEach((f, i) => {
    const n = `Fase ${i + 1} (${f.nome})`;
    const ref = f.referencia;

    // Estoque e posições da solução de referência.
    const usadas = L.contarPecas(ref);
    Object.entries(usadas).forEach(([t, q]) => checar(q <= (f.estoque[t] || 0), `${n}: referência usa ${q} ${t}, estoque ${f.estoque[t] || 0}`));
    const colocadas = [];
    ref.forEach(p => {
        checar(L.podeConstruir(f, L.todasAsPecas(f, colocadas), p.x, p.y), `${n}: célula (${p.x},${p.y}) da referência não é construível`);
        colocadas.push(p);
        if (p.tipo === 'separador') {
            const [a, b] = (f.limites && f.limites.abertura) || [1, 4];
            checar(p.abertura >= a && p.abertura <= b, `${n}: abertura fora dos limites`);
        }
    });
    f.pacotes.forEach((p, k) => checar(f.coletores.some(c => c.id === p.destino), `${n}: pacote ${k + 1} com destino inexistente`));

    // Solução existe, é determinística e não deixa alertas de erro.
    const r1 = L.simular(f, ref);
    const r2 = L.simular(f, ref);
    checar(r1.vitoria, `${n}: referência não vence (${r1.falha && r1.falha.mensagem})`);
    checar(JSON.stringify(r1) === JSON.stringify(r2), `${n}: simulação não determinística`);
    const avisos = L.avisosDeConexao(f, ref).filter(a => a.tipo === 'erro');
    checar(avisos.length === 0, `${n}: referência tem alertas: ${avisos.map(a => a.texto).join('; ')}`);

    // Sem montar nada a fase não pode estar resolvida, e toda falha explica o primeiro pacote afetado.
    const vazia = L.simular(f, []);
    checar(!vazia.vitoria, `${n}: vence sem nenhuma peça`);
    checar(vazia.falha && vazia.falha.mensagem && Number.isInteger(vazia.falha.pacote), `${n}: falha sem mensagem/pacote`);

    // Sem cada peça da referência, também precisa falhar (nenhuma peça sobrando na referência).
    ref.forEach((p, k) => {
        const sem = ref.filter((_, j) => j !== k);
        checar(!L.simular(f, sem).vitoria, `${n}: a peça ${p.tipo} em (${p.x},${p.y}) é dispensável na referência`);
    });
});

// Regras pontuais do separador (largura igual à abertura fica apoiada).
const f4 = L.FASES[3];
const comAbertura = a => [Object.assign({}, f4.referencia[0], { abertura: a })];
checar(!L.simular(f4, comAbertura(1)).vitoria, 'Separador: abertura 1 não deveria derrubar ninguém');
checar(L.simular(f4, comAbertura(3)).vitoria, 'Separador: abertura 3 deveria derrubar só P (G = 3 fica)');
checar(!L.simular(f4, comAbertura(4)).vitoria, 'Separador: abertura 4 deveria derrubar G também');
const msg = L.simular(f4, []).falha.mensagem;
checar(/caiu no coletor B, mas deveria ir para o coletor A/.test(msg), 'Mensagem do fosso vazio: ' + msg);
checar(L.simular(L.FASES[5], [L.FASES[5].referencia[0], Object.assign({}, L.FASES[5].referencia[1], { abertura: 2 })]).falha.mensagem.includes('seguiu pela esteira'), 'Mensagem "seguiu pela esteira" ausente');

// Desviador.
const dv = { grupo: 2, primeiro: 'frente' };
checar([0, 1, 2, 3, 4].map(k => L.saidaDoDesviador(dv, k)).join() === 'frente,frente,lado,lado,frente', 'Desviador grupo 2');
checar(L.saidaDoDesviador({ grupo: 1, primeiro: 'lado' }, 0) === 'lado', 'Desviador começando pelo lado');

if (erros.length) {
    console.error(erros.join('\n'));
    process.exit(1);
}
console.log(`OK: ${L.FASES.length} fases verificadas.`);
