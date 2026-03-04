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

                    <button type="submit" class="btn-elf text-white bg-mithril-primary/20">
                        <i class="fa-solid fa-filter mr-2"></i>
                        Filtrar
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

                            <tr class="{{ $rowClass }} hover:bg-white/5 transition duration-150">
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
                                                    class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500 hover:text-white transition shadow-lg border border-amber-500/20"
                                                    title="Confirmar Valor">
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
                                                        class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-white transition shadow-lg border border-emerald-500/20"
                                                        onclick="return confirm('Efetivar este lançamento no extrato real?')"
                                                        title="Pagar / Efetivar">
                                                        <i class="fa-solid fa-check text-xs"></i>
                                                    </button>
                                                </form>
                                                <a href="{{ route('mithril.pre-transacoes.form-confirmar', ['id' => $item->pre_transacao_id, 'mes' => $mes, 'ano' => $ano, 'conta_id' => $contaId]) }}"
                                                    class="text-slate-500 hover:text-slate-300 transition">
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
</x-Mithril::layout>
