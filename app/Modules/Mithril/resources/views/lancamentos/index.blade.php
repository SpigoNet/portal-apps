<x-Mithril::layout>
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
            <a href="{{ route('mithril.pre-transacoes.create') }}" class="btn-elf text-white">
                <i class="fa-solid fa-plus mr-2"></i>
                Novo Lançamento
            </a>
        </div>
    </x-slot>

    <div class="py-8 space-y-8">
        {{-- Filtros --}}
        <div class="mithril-theme-surface rounded-2xl border border-white/5 overflow-hidden">
            <div class="p-6">
                <form method="GET" action="{{ route('mithril.lancamentos.index') }}"
                    class="flex flex-wrap gap-6 items-end">

                    <div class="flex-1 min-w-[200px]">
                        <label class="block elf-title text-[9px] text-slate-400 mb-2">Período de Referência</label>
                        <div class="flex gap-2">
                            <select name="mes"
                                class="flex-1 rounded-lg border-white/10 bg-black/20 text-slate-200 focus:ring-mithril-accent focus:border-mithril-accent text-sm transition-colors">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $m == $mes ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="ano" value="{{ $ano }}"
                                class="w-24 rounded-lg border-white/10 bg-black/20 text-slate-200 focus:ring-mithril-accent focus:border-mithril-accent text-sm transition-colors">
                        </div>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="block elf-title text-[9px] text-slate-400 mb-2">Filtrar por Conta</label>
                        <select name="conta_id"
                            class="w-full rounded-lg border-white/10 bg-black/20 text-slate-200 focus:ring-mithril-accent focus:border-mithril-accent text-sm transition-colors">
                            <option value="">Todas as Contas</option>
                            @foreach ($contas as $c)
                                <option value="{{ $c->id }}" {{ $contaId == $c->id ? 'selected' : '' }}>
                                    {{ $c->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-elf text-white bg-mithril-primary/20 px-4 py-1.5">
                        <i class="fa-solid fa-filter mr-2"></i>
                        Filtrar
                    </button>
                    
                    <button type="button" id="toggle-efetivados-btn" class="btn-elf text-white bg-black/20 px-4 py-1.5"
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
                <table class="min-w-full divide-y divide-white/5">
                    <thead>
                        <tr class="bg-black/20">
                            <th class="px-6 py-4 text-left elf-title text-[9px] text-slate-400">Data</th>
                            <th class="px-6 py-4 text-left elf-title text-[9px] text-slate-400">Descrição / Conta</th>
                            <th class="px-6 py-4 text-right elf-title text-[9px] text-slate-400">Valor</th>
                            <th
                                class="px-6 py-4 text-right elf-title text-[9px] text-slate-400 border-l border-white/5">
                                Saldo Real</th>
                            <th class="px-6 py-4 text-right elf-title text-[9px] text-slate-400">Saldo Prev.</th>
                            <th class="px-6 py-4 text-center elf-title text-[9px] text-slate-400">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">

                        <tr class="bg-white/5 italic">
                            <td class="px-6 py-3 text-sm text-slate-400" data-label="Data">
                                01/{{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-3 text-sm text-slate-300" data-label="Descrição">Saldo Inicial do Período
                            </td>
                            <td class="px-6 py-3 text-right text-sm" data-label="Valor">--</td>
                            <td class="px-6 py-3 text-right text-sm border-l border-white/5 {{ $saldoInicial >= 0 ? 'text-emerald-400' : 'text-rose-400' }}"
                                data-label="Saldo Real">
                                R$ {{ number_format($saldoInicial, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-right text-sm {{ $saldoInicial >= 0 ? 'text-emerald-400' : 'text-rose-400' }}"
                                data-label="Saldo Prev.">
                                R$ {{ number_format($saldoInicial, 2, ',', '.') }}
                            </td>
                            <td data-label="Ações"></td>
                        </tr>

                        @forelse($listaCombinada as $index => $item)
                            @php
                                $proximoItem = $listaCombinada->get($index + 1);
                                $ultimoDoDia =
                                    !$proximoItem ||
                                    $proximoItem->data_efetiva->format('Y-m-d') != $item->data_efetiva->format('Y-m-d');

                                $rowClass = '';
                                if ($item->status === 'confirmado') {
                                    $rowClass = 'bg-amber-500/5';
                                } elseif ($item->status === 'pendente') {
                                    $rowClass = 'bg-orange-500/5';
                                }
                            @endphp

                            <tr class="{{ $rowClass }} hover:bg-white/5 transition duration-150 row-selectable"
                                data-valor="{{ $item->valor }}" data-status="{{ $item->status }}" data-index="{{ $index }}" data-dia="{{ $item->dia_vencimento }}" data-conta-id="{{ $item->conta->id }}">
                                <td class="px-6 py-4 whitespace-nowrap" data-label="Data">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-bold text-slate-200">{{ $item->data_efetiva->format('d/m') }}</span>
                                        <span
                                            class="text-[10px] text-slate-500 uppercase tracking-widest">{{ $item->data_efetiva->translatedFormat('D') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4" data-label="Descrição">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-slate-200">
                                            {{ $item->descricao }}
                                            @if ($item->meta_parcela)
                                                <span
                                                    class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black bg-white/5 text-slate-400 border border-white/5">
                                                    {{ $item->meta_parcela }}
                                                </span>
                                            @endif
                                        </span>
                                        <span class="text-xs text-slate-500">{{ $item->conta->nome }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black {{ $item->valor >= 0 ? 'text-emerald-400' : 'text-rose-400' }}"
                                    data-label="Valor">
                                    {{ $item->valor >= 0 ? '+' : '' }} R$
                                    {{ number_format($item->valor, 2, ',', '.') }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right border-l border-white/5"
                                    data-label="Saldo Real">
                                    @if ($ultimoDoDia)
                                        <span
                                            class="font-bold {{ $item->saldo_acumulado_efetivado >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                            R$ {{ number_format($item->saldo_acumulado_efetivado, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-slate-700">--</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right" data-label="Saldo Prev.">
                                    @if ($ultimoDoDia)
                                        <span
                                            class="font-medium {{ $item->saldo_acumulado_previsto >= 0 ? 'text-slate-300' : 'text-rose-400' }}">
                                            R$ {{ number_format($item->saldo_acumulado_previsto, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-slate-700">--</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium"
                                    data-label="Ações">
                                    <div class="flex justify-center items-center gap-2">
                                            @if ($item->tipo === 'projetado')
                                                @if ($item->status === 'pendente')
                                                    <a href="{{ route('mithril.pre-transacoes.form-confirmar', ['id' => $item->pre_transacao_id, 'mes' => $mes, 'ano' => $ano, 'conta_id' => $contaId]) }}"
                                                        class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500 hover:text-white transition shadow-lg border border-amber-500/20 js-open-confirm"
                                                        title="Confirmar Valor"
                                                        data-pt-id="{{ $item->pre_transacao_id }}"
                                                        data-confirm-url="{{ route('mithril.pre-transacoes.confirmar', ['id' => $item->pre_transacao_id]) }}"
                                                        data-mes="{{ $mes }}" data-ano="{{ $ano }}" data-conta-id="{{ $contaId }}">
                                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                    </a>
                                                @elseif($item->status === 'confirmado')
                                                    <form
                                                        action="{{ route('mithril.pre-transacoes.efetivar', ['id' => $item->pre_transacao_id]) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="mes" value="{{ $mes }}">
                                                        <input type="hidden" name="ano" value="{{ $ano }}">
                                                        <input type="hidden" name="conta_id" value="{{ $contaId }}">
                                                        <button type="submit"
                                                            class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-white transition shadow-lg border border-emerald-500/20 js-efetivar-btn"
                                                            data-pt-id="{{ $item->pre_transacao_id }}"
                                                            title="Pagar / Efetivar">
                                                            <i class="fa-solid fa-check text-xs"></i>
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('mithril.pre-transacoes.form-confirmar', ['id' => $item->pre_transacao_id, 'mes' => $mes, 'ano' => $ano, 'conta_id' => $contaId]) }}"
                                                        class="text-slate-500 hover:text-slate-300 transition js-open-confirm"
                                                        data-pt-id="{{ $item->pre_transacao_id }}"
                                                        data-confirm-url="{{ route('mithril.pre-transacoes.confirmar', ['id' => $item->pre_transacao_id]) }}"
                                                        data-mes="{{ $mes }}" data-ano="{{ $ano }}" data-conta-id="{{ $contaId }}">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </a>
                                                @endif
                                            @else
                                            <div
                                                class="px-3 py-1 bg-emerald-500/10 text-emerald-400 rounded-full elf-title text-[8px] flex items-center gap-1 border border-emerald-500/20">
                                                <i class="fa-solid fa-check-double text-[8px]"></i>
                                                Efetivado
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-4">
                                        <i class="fa-solid fa-scroll text-5xl text-slate-800"></i>
                                        <span class="text-slate-500 font-medium italic">Nenhum lançamento encontrado em
                                            seus registros.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Selected sum overlay -->
    <div id="selected-sum-overlay" class="hidden fixed bottom-6 right-6 z-50">
        <div class="mithril-theme-surface px-4 py-3 rounded-xl shadow-lg text-sm flex flex-col items-end gap-0.5">
            <div class="flex items-center gap-2">
                <div class="text-slate-400 text-xs" id="selected-count">0 selecionado(s)</div>
                <button type="button" id="clear-selection-btn" class="text-slate-600 hover:text-slate-300 transition text-xs" title="Limpar seleção">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="flex flex-col items-end gap-0.5 mt-1">
                <div class="text-emerald-400 font-semibold text-sm" id="selected-credits">Créditos: R$ 0,00</div>
                <div class="text-rose-400 font-semibold text-sm" id="selected-debits">Débitos: R$ 0,00</div>
                <div class="text-white font-bold text-lg border-t border-white/10 pt-1 w-full text-right" id="selected-sum">R$ 0,00</div>
            </div>
        </div>
    </div>

    <style>
        .selected-row {
            background-color: rgba(255,255,255,0.04) !important;
            box-shadow: inset 0 0 0 1px rgba(99,102,241,0.06);
        }
        table[data-hide-efetivados="true"] tr[data-status="efetivado"] {
            display: none;
        }
        #selected-sum-overlay .mithril-theme-surface {
            min-width: 220px;
        }
    </style>

    <!-- Confirm / Efetivar modals -->
    <div id="confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative mithril-theme-surface rounded-xl p-6 w-full max-w-md z-10">
            <h3 class="text-lg font-bold mb-3">Confirmar lançamento</h3>
            <form id="confirm-form">
                <input type="hidden" id="confirm-pt-id" name="pt_id">
                <input type="hidden" id="confirm-mes" name="mes" value="{{ $mes }}">
                <input type="hidden" id="confirm-ano" name="ano" value="{{ $ano }}">
                <input type="hidden" id="confirm-conta-id" name="conta_id">

                <div class="mb-3">
                    <label class="block text-sm text-slate-400 mb-1">Valor</label>
                    <input id="confirm-valor" name="valor" type="number" step="0.01"
                        class="w-full rounded-lg bg-black/10 border border-white/5 px-3 py-2 text-white">
                </div>

                <div class="mb-4 flex gap-2 items-end">
                    <div class="flex-1">
                        <label class="block text-sm text-slate-400 mb-1">Data</label>
                        <input id="confirm-data" name="data_vencimento" type="date"
                            class="w-full rounded-lg bg-black/10 border border-white/5 px-3 py-2 text-white">
                    </div>
                    <div>
                        <button type="button" id="confirm-use-today"
                            class="btn-elf text-white px-3 py-2">Hoje</button>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" id="confirm-cancel"
                        class="px-4 py-2 rounded-lg border border-white/5 text-slate-300">Cancelar</button>
                    <button type="submit" id="confirm-submit"
                        class="btn-elf text-white">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="efetivar-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative mithril-theme-surface rounded-xl p-6 w-full max-w-md z-10">
            <h3 class="text-lg font-bold mb-3">Efetivar lançamento</h3>
            <div class="mb-3 text-sm text-slate-300">Confirme os dados antes de registrar o pagamento.</div>
            <form id="efetivar-form">
                <input type="hidden" id="efetivar-pt-id" name="pt_id">
                <input type="hidden" id="efetivar-mes" name="mes" value="{{ $mes }}">
                <input type="hidden" id="efetivar-ano" name="ano" value="{{ $ano }}">
                <input type="hidden" id="efetivar-conta-id" name="conta_id">

                <div class="mb-2">
                    <div class="text-sm text-slate-400">Descrição</div>
                    <div id="efetivar-desc" class="text-sm font-medium text-slate-200"></div>
                </div>

                <div class="mb-3">
                    <div class="text-sm text-slate-400">Valor</div>
                    <div id="efetivar-valor" class="text-lg font-bold text-white"></div>
                </div>

                <div class="mb-4 flex gap-2 items-end">
                    <div class="flex-1">
                        <label class="block text-sm text-slate-400 mb-1">Data efetiva</label>
                        <input id="efetivar-data" name="data_efetiva" type="date"
                            class="w-full rounded-lg bg-black/10 border border-white/5 px-3 py-2 text-white">
                    </div>
                    <div>
                        <button type="button" id="efetivar-use-today" class="btn-elf text-white px-3 py-2">Hoje</button>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" id="efetivar-cancel"
                        class="px-4 py-2 rounded-lg border border-white/5 text-slate-300">Cancelar</button>
                    <button type="submit" id="efetivar-confirm" class="btn-elf text-white">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggle-efetivados-btn');
            const table = document.querySelector('table.min-w-full');
            const rows = Array.from(document.querySelectorAll('tbody tr.row-selectable'));
            const overlay = document.getElementById('selected-sum-overlay');
            const selectedSumEl = document.getElementById('selected-sum');
            const selectedCountEl = document.getElementById('selected-count');
            const confirmModal = document.getElementById('confirm-modal');
            const confirmForm = document.getElementById('confirm-form');
            const confirmPtId = document.getElementById('confirm-pt-id');
            const confirmValor = document.getElementById('confirm-valor');
            const confirmData = document.getElementById('confirm-data');
            const confirmContaId = document.getElementById('confirm-conta-id');

            const efetivarModal = document.getElementById('efetivar-modal');
            const efetivarForm = document.getElementById('efetivar-form');
            const efetivarPtId = document.getElementById('efetivar-pt-id');
            const efetivarDesc = document.getElementById('efetivar-desc');
            const efetivarValor = document.getElementById('efetivar-valor');
            const efetivarData = document.getElementById('efetivar-data');
            const efetivarContaId = document.getElementById('efetivar-conta-id');

            let activeRow = null; // referência à linha atualmente em edição

            function formatCurrency(value) {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
            }

            function updateOverlay() {
                const selected = rows.filter(r => r.classList.contains('selected-row'));
                const creditsEl = document.getElementById('selected-credits');
                const debitsEl = document.getElementById('selected-debits');
                if (selected.length === 0) {
                    overlay.classList.add('hidden');
                    selectedSumEl.textContent = formatCurrency(0);
                    selectedCountEl.textContent = '0 selecionado(s)';
                    return;
                }
                let credits = 0, debits = 0;
                selected.forEach(r => {
                    const v = parseFloat(r.dataset.valor || 0);
                    if (v > 0) credits += v;
                    else debits += v;
                });
                const total = credits + debits;
                creditsEl.textContent = 'Créditos: ' + formatCurrency(credits);
                debitsEl.textContent = 'Débitos: ' + formatCurrency(debits);
                selectedSumEl.textContent = formatCurrency(total);
                selectedCountEl.textContent = selected.length + ' selecionado(s)';
                overlay.classList.remove('hidden');
            }

            // Row click selection; ignore clicks on interactive elements
            rows.forEach(r => {
                r.addEventListener('click', function (e) {
                    if (e.target.closest('a, button, form, input, select')) return;
                    r.classList.toggle('selected-row');
                    updateOverlay();
                });
            });

            // Clear selection button
            document.getElementById('clear-selection-btn').addEventListener('click', function () {
                rows.forEach(r => r.classList.remove('selected-row'));
                updateOverlay();
            });

            // Prevent action buttons from selecting the row
            document.querySelectorAll('tbody tr.row-selectable a, tbody tr.row-selectable button, tbody tr.row-selectable form').forEach(el => {
                el.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            });

            // Delegated handler: Open confirm modal
            document.addEventListener('click', function (e) {
                const a = e.target.closest('a.js-open-confirm');
                if (!a) return;
                e.preventDefault();
                const ptId = a.dataset.ptId;
                const row = a.closest('tr');
                activeRow = row;

                // preenche valores
                confirmPtId.value = ptId;
                confirmValor.value = parseFloat(row.dataset.valor || 0).toFixed(2);
                const dia = row.dataset.dia;
                if (dia) {
                    // usa ano/mes atuais do filtro
                    const y = document.getElementById('confirm-ano').value || new Date().getFullYear();
                    const m = document.getElementById('confirm-mes').value || (new Date().getMonth()+1);
                    // pad
                    const mm = String(m).padStart(2, '0');
                    const dd = String(dia).padStart(2, '0');
                    confirmData.value = `${y}-${mm}-${dd}`;
                } else {
                    confirmData.value = new Date().toISOString().slice(0,10);
                }
                confirmContaId.value = a.dataset.contaId || document.getElementById('confirm-conta-id').value || '';
                confirmModal.classList.remove('hidden');
            });

            // confirm modal - use today
            document.getElementById('confirm-use-today').addEventListener('click', function () {
                confirmData.value = new Date().toISOString().slice(0,10);
            });

            document.getElementById('confirm-cancel').addEventListener('click', function () {
                confirmModal.classList.add('hidden');
            });

            // submit confirm via AJAX
            confirmForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const ptId = confirmPtId.value;
                const url = document.querySelector('a.js-open-confirm[data-pt-id="' + ptId + '"]')?.dataset.confirmUrl || `/mithril/pre-transacao/${ptId}/confirmar`;
                const data = new FormData();
                data.append('valor', confirmValor.value);
                data.append('data_vencimento', confirmData.value);
                data.append('mes', document.getElementById('confirm-mes').value || '{{ $mes }}');
                data.append('ano', document.getElementById('confirm-ano').value || '{{ $ano }}');
                data.append('conta_id', confirmContaId.value || '');

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: data,
                    credentials: 'same-origin'
                }).then(r => r.json()).then(json => {
                    if (json?.data) {
                        // atualiza a linha
                        const row = activeRow;
                        if (row) {
                            row.dataset.valor = json.data.valor_parcela;
                            row.dataset.status = json.data.status || 'confirmado';
                            // atualiza célula de valor (3rd td)
                            const valorTd = row.querySelector('td[data-label="Valor"]');
                            if (valorTd) {
                                const v = parseFloat(json.data.valor_parcela || 0);
                                const sign = v >= 0 ? '+' : '';
                                valorTd.innerHTML = `${sign} ${formatCurrency(v)}`;
                                if (v >= 0) {
                                    valorTd.classList.remove('text-rose-400');
                                    valorTd.classList.add('text-emerald-400');
                                } else {
                                    valorTd.classList.remove('text-emerald-400');
                                    valorTd.classList.add('text-rose-400');
                                }
                            }

                            // Atualiza ação: troca para botão de efetivar + editar
                            const actionsTd = row.querySelector('td[data-label="Ações"] .flex');
                            if (actionsTd) {
                                actionsTd.innerHTML = `
                                    <form action="/mithril/pre-transacao/${ptId}/efetivar" method="POST" class="inline">
                                        <input type="hidden" name="_token" value="${csrf}">
                                        <input type="hidden" name="mes" value="${document.getElementById('confirm-mes').value}">
                                        <input type="hidden" name="ano" value="${document.getElementById('confirm-ano').value}">
                                        <input type="hidden" name="conta_id" value="${confirmContaId.value}">
                                        <button type="button" class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-white transition shadow-lg border border-emerald-500/20 js-efetivar-btn" data-pt-id="${ptId}" title="Pagar / Efetivar">
                                            <i class="fa-solid fa-check text-xs"></i>
                                        </button>
                                    </form>
                                    <a href="/mithril/pre-transacao/${ptId}/confirmar" class="text-slate-500 hover:text-slate-300 transition js-open-confirm" data-pt-id="${ptId}" data-confirm-url="/mithril/pre-transacao/${ptId}/confirmar">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </a>
                                `;
                            }
                        }
                    }
                    confirmModal.classList.add('hidden');
                }).catch(err => {
                    console.error(err);
                    alert('Ocorreu um erro ao confirmar.');
                });
            });

            // intercept efetivar button to open modal
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-efetivar-btn');
                if (!btn) return;
                e.preventDefault();
                const ptId = btn.dataset.ptId;
                const row = btn.closest('tr');
                activeRow = row;
                efetivarPtId.value = ptId;
                efetivarContaId.value = row.dataset.contaId || document.getElementById('efetivar-conta-id')?.value || '';
                efetivarDesc.textContent = row.querySelector('td[data-label="Descrição"] .text-sm')?.textContent?.trim() || '';
                const v = parseFloat(row.dataset.valor || 0);
                efetivarValor.textContent = formatCurrency(v);
                // set date
                const dia = row.dataset.dia;
                if (dia) {
                    const y = document.getElementById('efetivar-ano').value || new Date().getFullYear();
                    const m = document.getElementById('efetivar-mes').value || (new Date().getMonth()+1);
                    const mm = String(m).padStart(2, '0');
                    const dd = String(dia).padStart(2, '0');
                    efetivarData.value = `${y}-${mm}-${dd}`;
                } else {
                    efetivarData.value = new Date().toISOString().slice(0,10);
                }
                efetivarModal.classList.remove('hidden');
            });

            document.getElementById('efetivar-use-today').addEventListener('click', function () {
                efetivarData.value = new Date().toISOString().slice(0,10);
            });

            document.getElementById('efetivar-cancel').addEventListener('click', function () {
                efetivarModal.classList.add('hidden');
            });

            // submit efetivar via AJAX
            efetivarForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const ptId = efetivarPtId.value;
                const url = `/mithril/pre-transacao/${ptId}/efetivar`;
                const data = new FormData();
                data.append('mes', document.getElementById('efetivar-mes').value || '{{ $mes }}');
                data.append('ano', document.getElementById('efetivar-ano').value || '{{ $ano }}');
                data.append('conta_id', efetivarContaId.value || '');

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: data,
                    credentials: 'same-origin'
                }).then(r => r.json()).then(json => {
                    if (json?.data?.transacao) {
                        const row = activeRow;
                        if (row) {
                            row.dataset.status = 'efetivado';
                            // update date cell
                            const dateTd = row.querySelector('td[data-label="Data"] .text-sm');
                            if (dateTd && json.data.transacao.data_efetiva) {
                                const d = new Date(json.data.transacao.data_efetiva);
                                const dd = String(d.getDate()).padStart(2, '0');
                                const mm = String(d.getMonth()+1).padStart(2, '0');
                                dateTd.textContent = `${dd}/${mm}`;
                                // also update dataset dia
                                row.dataset.dia = d.getDate();
                            }

                            // update actions cell to Efetivado badge
                            const actionsTd = row.querySelector('td[data-label="Ações"] .flex');
                            if (actionsTd) {
                                actionsTd.innerHTML = `
                                    <div class="px-3 py-1 bg-emerald-500/10 text-emerald-400 rounded-full elf-title text-[8px] flex items-center gap-1 border border-emerald-500/20">
                                        <i class="fa-solid fa-check-double text-[8px]"></i>
                                        Efetivado
                                    </div>
                                `;
                            }
                        }
                    }
                    efetivarModal.classList.add('hidden');
                }).catch(err => {
                    console.error(err);
                    alert('Ocorreu um erro ao efetivar.');
                });
            });

            // Toggle Ocultar Efetivados
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    const isHidden = table.getAttribute('data-hide-efetivados') === 'true';
                    const newVal = isHidden ? 'false' : 'true';
                    table.setAttribute('data-hide-efetivados', newVal);

                    const icon = toggleBtn.querySelector('i');
                    if (newVal === 'true') {
                        toggleBtn.innerHTML = '<i class="fa-solid fa-eye mr-2"></i> Mostrar Efetivados';
                        toggleBtn.title = 'Mostrar lançamentos já efetivados';
                    } else {
                        toggleBtn.innerHTML = '<i class="fa-solid fa-eye-slash mr-2"></i> Ocultar Efetivados';
                        toggleBtn.title = 'Oculta/mostra lançamentos já efetivados';
                    }
                });
            }
        });
    </script>

</x-Mithril::layout>
