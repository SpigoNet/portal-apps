<x-Mithril::layout>
    @php
        $classeInput = 'w-full rounded-lg bg-black/20 border border-white/10 px-3 py-2 text-sm text-slate-200 focus:ring-mithril-accent focus:border-mithril-accent transition-colors';
        $classeLabel = 'block elf-title text-[9px] text-slate-400 mb-2';
        $classeBtnCancelar = 'px-4 py-2 rounded-lg border border-white/5 text-slate-300 hover:bg-white/5 transition';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-3">
                <div class="p-2 mithril-theme-button rounded-lg text-white shadow-lg">
                    <i class="fa-solid fa-scroll text-xl"></i>
                </div>
                <div>
                    <h2 class="elf-title text-xl text-white leading-tight">
                        Fluxo de Caixa
                        <span class="block text-[10px] text-slate-400 font-medium uppercase tracking-[0.2em]">Pergaminho
                            de Entradas e Saídas</span>
                    </h2>
                </div>
            </div>
            <button type="button" id="novo-lancamento-btn" class="btn-elf text-white">
                <i class="fa-solid fa-plus mr-2"></i>
                Novo Lançamento
            </button>
        </div>
    </x-slot>

    <div class="py-8 space-y-8">
        {{-- Filtros --}}
        <div class="mithril-theme-surface rounded-2xl border border-white/5 overflow-hidden">
            <div class="p-6">
                <form method="GET" id="filtros-form" action="{{ route('mithril.lancamentos.index') }}"
                    class="flex flex-wrap gap-6 items-end">

                    <div class="flex-1 min-w-[200px]">
                        <label class="{{ $classeLabel }}">Período de Referência</label>
                        <div class="flex gap-2">
                            <select name="mes" class="flex-1 {{ $classeInput }}">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $m == $mes ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="ano" value="{{ $ano }}" class="w-24 {{ $classeInput }}">
                        </div>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="{{ $classeLabel }}">Filtrar por Conta</label>
                        <select name="conta_id" class="{{ $classeInput }}">
                            <option value="">Todas as Contas</option>
                            @foreach ($contas as $c)
                                <option value="{{ $c->id }}" {{ $contaId == $c->id ? 'selected' : '' }}>
                                    {{ $c->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-elf text-white bg-mithril-primary/20 px-4 py-1.5 rounded-lg">
                        <i class="fa-solid fa-filter mr-2"></i>
                        Filtrar
                    </button>

                    <button type="button" id="toggle-efetivados-btn"
                        class="btn-elf text-white bg-black/20 px-4 py-1.5 rounded-lg"
                        title="Oculta/mostra lançamentos já efetivados">
                        <i class="fa-solid fa-eye-slash mr-2"></i>
                        Ocultar Efetivados
                    </button>
                </form>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="mithril-theme-surface rounded-2xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto mobile-stack">
                <table id="tabela-lancamentos" class="min-w-full divide-y divide-white/5">
                    <thead>
                        <tr class="bg-black/20">
                            <th class="px-6 py-4 text-left elf-title text-[9px] text-slate-400">Data</th>
                            <th class="px-6 py-4 text-left elf-title text-[9px] text-slate-400">Descrição / Conta</th>
                            <th class="px-6 py-4 text-right elf-title text-[9px] text-slate-400">Valor</th>
                            <th class="px-6 py-4 text-right elf-title text-[9px] text-slate-400 border-l border-white/5">
                                Saldo Real</th>
                            <th class="px-6 py-4 text-right elf-title text-[9px] text-slate-400">Saldo Prev.</th>
                            <th class="px-6 py-4 text-center elf-title text-[9px] text-slate-400">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="lancamentos-linhas" class="divide-y divide-white/5">
                        @include('Mithril::lancamentos._linhas')
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Soma dos selecionados --}}
    <div id="selected-sum-overlay" class="hidden fixed bottom-6 right-6 z-40">
        <div class="mithril-theme-surface px-4 py-3 rounded-xl shadow-lg text-sm flex flex-col items-end gap-0.5">
            <div class="flex items-center gap-2">
                <div class="text-slate-400 text-xs" id="selected-count">0 selecionado(s)</div>
                <button type="button" id="clear-selection-btn"
                    class="text-slate-600 hover:text-slate-300 transition text-xs" title="Limpar seleção">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="flex flex-col items-end gap-0.5 mt-1">
                <div class="text-emerald-400 font-semibold text-sm" id="selected-credits">Créditos: R$ 0,00</div>
                <div class="text-rose-400 font-semibold text-sm" id="selected-debits">Débitos: R$ 0,00</div>
                <div class="text-white font-bold text-lg border-t border-white/10 pt-1 w-full text-right"
                    id="selected-sum">R$ 0,00</div>
            </div>
        </div>
    </div>

    {{-- Aviso de sucesso/erro --}}
    <div id="toast" role="status"
        class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-4 py-2 rounded-lg text-sm font-medium shadow-lg"></div>

    {{-- Diálogo: confirmar valor e data --}}
    <dialog id="dlg-confirmar" class="mithril-theme-surface text-slate-200 rounded-xl p-6 w-full max-w-md backdrop:bg-black/60">
        <form method="dialog" id="form-confirmar" class="space-y-4">
            <h3 class="text-lg font-bold">Confirmar lançamento</h3>
            <p class="text-sm text-slate-400" data-campo="descricao"></p>
            <div>
                <label class="{{ $classeLabel }}">Valor</label>
                <input name="valor" type="number" step="0.01" required class="{{ $classeInput }}">
            </div>
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="{{ $classeLabel }}">Data</label>
                    <input name="data_vencimento" type="date" required class="{{ $classeInput }}">
                </div>
                <button type="button" data-hoje class="btn-elf text-white px-3 py-2">Hoje</button>
            </div>
            <p class="text-sm text-rose-400 hidden" data-erro></p>
            <div class="flex justify-end gap-2">
                <button type="button" data-fechar class="{{ $classeBtnCancelar }}">Cancelar</button>
                <button type="submit" class="btn-elf text-white">Salvar</button>
            </div>
        </form>
    </dialog>

    {{-- Diálogo: efetivar --}}
    <dialog id="dlg-efetivar" class="mithril-theme-surface text-slate-200 rounded-xl p-6 w-full max-w-md backdrop:bg-black/60">
        <form method="dialog" id="form-efetivar" class="space-y-4">
            <h3 class="text-lg font-bold">Efetivar lançamento</h3>
            <div>
                <div class="text-sm text-slate-400">Descrição</div>
                <div class="text-sm font-medium" data-campo="descricao"></div>
            </div>
            <div>
                <div class="text-sm text-slate-400">Valor</div>
                <div class="text-lg font-bold text-white" data-campo="valor"></div>
            </div>
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="{{ $classeLabel }}">Data efetiva</label>
                    <input name="data_efetiva" type="date" required class="{{ $classeInput }}">
                </div>
                <button type="button" data-hoje class="btn-elf text-white px-3 py-2">Hoje</button>
            </div>
            <p class="text-sm text-rose-400 hidden" data-erro></p>
            <div class="flex justify-end gap-2">
                <button type="button" data-fechar class="{{ $classeBtnCancelar }}">Cancelar</button>
                <button type="submit" class="btn-elf text-white">Confirmar</button>
            </div>
        </form>
    </dialog>

    {{-- Diálogo: estornar --}}
    <dialog id="dlg-estornar" class="mithril-theme-surface text-slate-200 rounded-xl p-6 w-full max-w-md backdrop:bg-black/60">
        <form method="dialog" id="form-estornar" class="space-y-4">
            <h3 class="text-lg font-bold">Estornar transação</h3>
            <p class="text-sm text-slate-300">
                Desfazer a efetivação de <strong data-campo="descricao"></strong>
                (<span data-campo="valor"></span>)? A transação será apagada e o lançamento volta a ser previsto.
            </p>
            <p class="text-sm text-rose-400 hidden" data-erro></p>
            <div class="flex justify-end gap-2">
                <button type="button" data-fechar class="{{ $classeBtnCancelar }}">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-rose-500/80 hover:bg-rose-500 text-white font-bold transition">Estornar</button>
            </div>
        </form>
    </dialog>

    {{-- Diálogo: novo lançamento --}}
    <dialog id="dlg-novo" class="mithril-theme-surface text-slate-200 rounded-xl p-6 w-full max-w-lg backdrop:bg-black/60">
        <form method="dialog" id="form-novo" class="space-y-4">
            <h3 class="text-lg font-bold">Novo lançamento</h3>

            <div>
                <label class="{{ $classeLabel }}">Descrição</label>
                <input name="descricao" type="text" required maxlength="255" class="{{ $classeInput }}"
                    placeholder="Ex: Assinatura Netflix, Aluguel, etc">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $classeLabel }}">Valor da parcela (R$)</label>
                    <input name="valor_parcela" type="text" inputmode="decimal" required placeholder="0,00"
                        class="{{ $classeInput }} text-right">
                </div>
                <div>
                    <label class="{{ $classeLabel }}">Dia do vencimento</label>
                    <input name="dia_vencimento" type="number" min="1" max="31" required class="{{ $classeInput }}">
                </div>
            </div>

            <div>
                <label class="{{ $classeLabel }}">Conta</label>
                <select name="conta_id" required class="{{ $classeInput }}">
                    @foreach ($contasNormais as $c)
                        <option value="{{ $c->id }}" {{ $contaId == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $classeLabel }}">Operação</label>
                    <select name="operacao" class="{{ $classeInput }}">
                        <option value="debito">Débito / Despesa</option>
                        <option value="credito">Crédito / Receita</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $classeLabel }}">Modelo</label>
                    <select name="tipo" class="{{ $classeInput }}">
                        <option value="recorrente">Mensal (fixo)</option>
                        <option value="parcelada">Parcelado</option>
                    </select>
                </div>
            </div>

            <div data-parcelas class="hidden grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $classeLabel }}">Total de parcelas</label>
                    <input name="total_parcelas" type="number" min="1" class="{{ $classeInput }}">
                </div>
                <div>
                    <label class="{{ $classeLabel }}">Primeiro vencimento</label>
                    <input name="data_inicio" type="date" class="{{ $classeInput }}">
                </div>
            </div>

            <p class="text-sm text-rose-400 hidden" data-erro></p>
            <div class="flex justify-end gap-2">
                <button type="button" data-fechar class="{{ $classeBtnCancelar }}">Cancelar</button>
                <button type="submit" class="btn-elf text-white">Cadastrar</button>
            </div>
        </form>
    </dialog>

    <style>
        .selected-row {
            background-color: rgba(255, 255, 255, 0.04) !important;
            box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.06);
        }

        table[data-hide-efetivados="true"] tr[data-status="efetivado"] {
            display: none;
        }

        #selected-sum-overlay .mithril-theme-surface {
            min-width: 220px;
        }

        #lancamentos-linhas.carregando {
            opacity: .55;
            transition: opacity .15s;
        }

        @keyframes destaque-linha {
            from { box-shadow: inset 0 0 0 2px rgba(125, 211, 252, .8); }
            to { box-shadow: inset 0 0 0 2px rgba(125, 211, 252, 0); }
        }

        .linha-destaque {
            animation: destaque-linha 1.6s ease-out;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const URL_LISTA = @json(route('mithril.lancamentos.index'));
            const URL_BASE = @json(url('/mithril'));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const filtrosForm = document.getElementById('filtros-form');
            const tabela = document.getElementById('tabela-lancamentos');
            const corpo = document.getElementById('lancamentos-linhas');
            const toggleBtn = document.getElementById('toggle-efetivados-btn');
            const overlay = document.getElementById('selected-sum-overlay');
            const toastEl = document.getElementById('toast');

            const hoje = () => {
                const d = new Date();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${d.getFullYear()}-${mm}-${dd}`;
            };
            const moeda = v => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

            // ---------- Aviso ----------
            let toastTimer = null;
            function avisar(mensagem, erro = false) {
                toastEl.textContent = mensagem;
                toastEl.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] px-4 py-2 rounded-lg text-sm font-medium shadow-lg '
                    + (erro ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 3500);
            }

            // ---------- Requisições ----------
            function filtrosAtuais() {
                const dados = new FormData(filtrosForm);
                return {
                    mes: dados.get('mes') || '',
                    ano: dados.get('ano') || '',
                    conta_id: dados.get('conta_id') || '',
                };
            }

            async function enviar(url, dados) {
                const corpoReq = new FormData();
                Object.entries(dados).forEach(([k, v]) => corpoReq.append(k, v ?? ''));
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: corpoReq,
                    credentials: 'same-origin',
                });
                const json = await resp.json().catch(() => ({}));
                if (!resp.ok) {
                    const erros = json.errors ? Object.values(json.errors).flat() : [];
                    throw new Error(erros[0] || json.message || 'Não foi possível concluir a operação.');
                }
                return json;
            }

            // ---------- Seleção (soma) ----------
            const selecionadas = new Set();
            const chaveLinha = tr => tr.dataset.ptId ? 'pt:' + tr.dataset.ptId : 'tx:' + tr.dataset.txId;
            const linhas = () => Array.from(corpo.querySelectorAll('tr.row-selectable'));

            function atualizarSoma() {
                const marcadas = linhas().filter(r => r.classList.contains('selected-row'));
                if (marcadas.length === 0) {
                    overlay.classList.add('hidden');
                    return;
                }
                let creditos = 0, debitos = 0;
                marcadas.forEach(r => {
                    const v = parseFloat(r.dataset.valor || 0);
                    if (v > 0) creditos += v; else debitos += v;
                });
                document.getElementById('selected-credits').textContent = 'Créditos: ' + moeda(creditos);
                document.getElementById('selected-debits').textContent = 'Débitos: ' + moeda(debitos);
                document.getElementById('selected-sum').textContent = moeda(creditos + debitos);
                document.getElementById('selected-count').textContent = marcadas.length + ' selecionado(s)';
                overlay.classList.remove('hidden');
            }

            function reaplicarSelecao() {
                const existentes = new Set();
                linhas().forEach(r => {
                    const chave = chaveLinha(r);
                    existentes.add(chave);
                    r.classList.toggle('selected-row', selecionadas.has(chave));
                });
                selecionadas.forEach(c => { if (!existentes.has(c)) selecionadas.delete(c); });
                atualizarSoma();
            }

            document.getElementById('clear-selection-btn')?.addEventListener('click', () => {
                selecionadas.clear();
                reaplicarSelecao();
            });

            // ---------- Recarga dinâmica das linhas ----------
            async function recarregarLinhas(destaque = null) {
                const params = new URLSearchParams(filtrosAtuais());
                params.set('_parcial', '1');
                corpo.classList.add('carregando');
                try {
                    const resp = await fetch(`${URL_LISTA}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!resp.ok) throw new Error('Falha ao atualizar a lista.');
                    corpo.innerHTML = await resp.text();
                    reaplicarSelecao();
                    if (destaque) {
                        const alvo = linhas().find(r => (destaque.ptId && r.dataset.ptId === String(destaque.ptId))
                            || (destaque.txId && r.dataset.txId === String(destaque.txId)));
                        if (alvo) alvo.classList.add('linha-destaque');
                    }
                } catch (e) {
                    avisar(e.message, true);
                } finally {
                    corpo.classList.remove('carregando');
                }
            }

            // Filtros sem recarregar a página (mantém URL compartilhável e histórico)
            filtrosForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const params = new URLSearchParams(filtrosAtuais());
                if (!params.get('conta_id')) params.delete('conta_id');
                history.pushState(null, '', `${URL_LISTA}?${params}`);
                recarregarLinhas();
            });

            window.addEventListener('popstate', function () {
                const params = new URLSearchParams(location.search);
                filtrosForm.elements.mes.value = params.get('mes') || filtrosForm.elements.mes.value;
                filtrosForm.elements.ano.value = params.get('ano') || filtrosForm.elements.ano.value;
                filtrosForm.elements.conta_id.value = params.get('conta_id') || '';
                recarregarLinhas();
            });

            // ---------- Ocultar efetivados (persistido) ----------
            function aplicarOcultar(ocultar) {
                tabela.setAttribute('data-hide-efetivados', ocultar ? 'true' : 'false');
                toggleBtn.innerHTML = ocultar
                    ? '<i class="fa-solid fa-eye mr-2"></i> Mostrar Efetivados'
                    : '<i class="fa-solid fa-eye-slash mr-2"></i> Ocultar Efetivados';
                toggleBtn.title = ocultar ? 'Mostrar lançamentos já efetivados' : 'Oculta/mostra lançamentos já efetivados';
                try { localStorage.setItem('mithril.ocultarEfetivados', ocultar ? '1' : '0'); } catch (e) { /* storage indisponível */ }
            }
            try { aplicarOcultar(localStorage.getItem('mithril.ocultarEfetivados') === '1'); } catch (e) { /* storage indisponível */ }
            toggleBtn.addEventListener('click', () => aplicarOcultar(tabela.getAttribute('data-hide-efetivados') !== 'true'));

            // ---------- Diálogos ----------
            const dialogos = {
                confirmar: document.getElementById('dlg-confirmar'),
                efetivar: document.getElementById('dlg-efetivar'),
                estornar: document.getElementById('dlg-estornar'),
                novo: document.getElementById('dlg-novo'),
            };

            function mostrarErro(dlg, mensagem) {
                const el = dlg.querySelector('[data-erro]');
                el.textContent = mensagem || '';
                el.classList.toggle('hidden', !mensagem);
            }

            function abrir(dlg) {
                mostrarErro(dlg, '');
                dlg.showModal();
            }

            Object.values(dialogos).forEach(dlg => {
                dlg.querySelectorAll('[data-fechar]').forEach(b => b.addEventListener('click', () => dlg.close()));
                // fecha ao clicar no fundo (fora do conteúdo)
                dlg.addEventListener('mousedown', e => { if (e.target === dlg) dlg.close(); });
                dlg.querySelector('[data-hoje]')?.addEventListener('click', () => {
                    const campo = dlg.querySelector('input[type="date"]');
                    campo.value = hoje();
                });
            });

            // Envio padrão: desabilita o botão, trata erro no próprio diálogo e recarrega as linhas
            function ligarEnvio(dlg, montar) {
                dlg.querySelector('form').addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const botao = e.submitter || dlg.querySelector('button[type="submit"]');
                    botao.disabled = true;
                    mostrarErro(dlg, '');
                    try {
                        const { url, dados, destaque } = montar();
                        const json = await enviar(url, dados);
                        dlg.close();
                        avisar(json.message || 'Operação concluída.');
                        await recarregarLinhas(destaque);
                    } catch (err) {
                        mostrarErro(dlg, err.message);
                    } finally {
                        botao.disabled = false;
                    }
                });
            }

            let linhaAtiva = null;

            ligarEnvio(dialogos.confirmar, () => {
                const f = dialogos.confirmar.querySelector('form').elements;
                return {
                    url: `${URL_BASE}/pre-transacao/${linhaAtiva.dataset.ptId}/confirmar`,
                    dados: { valor: f.valor.value, data_vencimento: f.data_vencimento.value, ...filtrosAtuais() },
                    destaque: { ptId: linhaAtiva.dataset.ptId },
                };
            });

            ligarEnvio(dialogos.efetivar, () => {
                const f = dialogos.efetivar.querySelector('form').elements;
                return {
                    url: `${URL_BASE}/pre-transacao/${linhaAtiva.dataset.ptId}/efetivar`,
                    dados: { data_efetiva: f.data_efetiva.value, ...filtrosAtuais() },
                    destaque: { ptId: linhaAtiva.dataset.ptId },
                };
            });

            ligarEnvio(dialogos.estornar, () => ({
                url: `${URL_BASE}/transacao/${linhaAtiva.dataset.txId}/estornar`,
                dados: filtrosAtuais(),
                destaque: linhaAtiva.dataset.ptId ? { ptId: linhaAtiva.dataset.ptId } : null,
            }));

            // Novo lançamento
            const formNovo = dialogos.novo.querySelector('form');
            const blocoParcelas = dialogos.novo.querySelector('[data-parcelas]');
            const alternarParcelas = () => {
                const parcelada = formNovo.elements.tipo.value === 'parcelada';
                blocoParcelas.classList.toggle('hidden', !parcelada);
                formNovo.elements.total_parcelas.required = parcelada;
                formNovo.elements.data_inicio.required = parcelada;
            };
            formNovo.elements.tipo.addEventListener('change', alternarParcelas);

            // O botão fica no slot do header (renderizado fora deste DOM inicial): delegação no document
            document.addEventListener('click', e => {
                if (!e.target.closest('#novo-lancamento-btn')) return;
                formNovo.reset();
                formNovo.elements.dia_vencimento.value = new Date().getDate();
                formNovo.elements.data_inicio.value = hoje();
                const contaFiltro = filtrosAtuais().conta_id;
                if (contaFiltro) formNovo.elements.conta_id.value = contaFiltro;
                alternarParcelas();
                abrir(dialogos.novo);
                formNovo.elements.descricao.focus();
            });

            ligarEnvio(dialogos.novo, () => {
                const dados = Object.fromEntries(new FormData(formNovo));
                if (dados.tipo !== 'parcelada') delete dados.total_parcelas;
                return { url: `${URL_BASE}/pre-transacoes`, dados, destaque: null };
            });

            // ---------- Ações nas linhas ----------
            corpo.addEventListener('click', async function (e) {
                const botao = e.target.closest('[data-acao]');
                const linha = e.target.closest('tr.row-selectable');
                if (!linha) return;

                // Clique fora dos botões: seleciona a linha para somar
                if (!botao) {
                    if (e.target.closest('a, input, select')) return;
                    const chave = chaveLinha(linha);
                    if (selecionadas.has(chave)) selecionadas.delete(chave); else selecionadas.add(chave);
                    reaplicarSelecao();
                    return;
                }

                linhaAtiva = linha;
                const acao = botao.dataset.acao;
                const valor = parseFloat(linha.dataset.valor || 0);
                const descricao = linha.dataset.descricao;

                if (acao === 'paguei-agora') {
                    botao.disabled = true;
                    try {
                        const json = await enviar(`${URL_BASE}/pre-transacao/${linha.dataset.ptId}/paguei-agora`, {
                            valor: linha.dataset.valor,
                            ...filtrosAtuais(),
                        });
                        avisar(json.message || 'Pagamento registrado.');
                        await recarregarLinhas({ ptId: linha.dataset.ptId });
                    } catch (err) {
                        botao.disabled = false;
                        avisar(err.message, true);
                    }
                    return;
                }

                if (acao === 'confirmar') {
                    const f = dialogos.confirmar.querySelector('form').elements;
                    dialogos.confirmar.querySelector('[data-campo="descricao"]').textContent = descricao;
                    f.valor.value = valor.toFixed(2);
                    f.data_vencimento.value = linha.dataset.data;
                    abrir(dialogos.confirmar);
                } else if (acao === 'efetivar') {
                    const f = dialogos.efetivar.querySelector('form').elements;
                    dialogos.efetivar.querySelector('[data-campo="descricao"]').textContent = descricao;
                    dialogos.efetivar.querySelector('[data-campo="valor"]').textContent = moeda(valor);
                    f.data_efetiva.value = linha.dataset.data;
                    abrir(dialogos.efetivar);
                } else if (acao === 'estornar') {
                    dialogos.estornar.querySelector('[data-campo="descricao"]').textContent = descricao;
                    dialogos.estornar.querySelector('[data-campo="valor"]').textContent = moeda(valor);
                    abrir(dialogos.estornar);
                }
            });

            reaplicarSelecao();
        });
    </script>

</x-Mithril::layout>
