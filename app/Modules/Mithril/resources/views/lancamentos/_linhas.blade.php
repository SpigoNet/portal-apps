@php
    $saldoInicialClasse = $saldoInicial >= 0 ? 'text-emerald-400' : 'text-rose-400';
@endphp

<tr class="bg-white/5 italic">
    <td class="px-6 py-3 text-sm text-slate-400" data-label="Data">
        01/{{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}</td>
    <td class="px-6 py-3 text-sm text-slate-300" data-label="Descrição">Saldo Inicial do Período</td>
    <td class="px-6 py-3 text-right text-sm" data-label="Valor">--</td>
    <td class="px-6 py-3 text-right text-sm border-l border-white/5 {{ $saldoInicialClasse }}" data-label="Saldo Real">
        R$ {{ number_format($saldoInicial, 2, ',', '.') }}
    </td>
    <td class="px-6 py-3 text-right text-sm {{ $saldoInicialClasse }}" data-label="Saldo Prev.">
        R$ {{ number_format($saldoInicial, 2, ',', '.') }}
    </td>
    <td data-label="Ações"></td>
</tr>

@forelse($listaCombinada as $index => $item)
    @php
        $proximoItem = $listaCombinada->get($index + 1);
        $ultimoDoDia = !$proximoItem || $proximoItem->data_efetiva->format('Y-m-d') != $item->data_efetiva->format('Y-m-d');

        $rowClass = match ($item->status) {
            'confirmado' => 'bg-amber-500/5',
            'pendente' => 'bg-orange-500/5',
            default => '',
        };
        $isCredito = $item->valor >= 0;
    @endphp

    <tr class="{{ $rowClass }} hover:bg-white/5 transition duration-150 row-selectable"
        data-valor="{{ $item->valor }}" data-status="{{ $item->status }}" data-dia="{{ $item->dia_vencimento }}"
        data-conta-id="{{ $item->conta->id }}" data-pt-id="{{ $item->pre_transacao_id }}"
        data-tx-id="{{ $item->id }}" data-descricao="{{ $item->descricao }}"
        data-data="{{ $item->data_efetiva->format('Y-m-d') }}">
        <td class="px-6 py-4 whitespace-nowrap" data-label="Data">
            <div class="flex flex-col">
                <span class="text-sm font-bold text-slate-200">{{ $item->data_efetiva->format('d/m') }}</span>
                <span class="text-[10px] text-slate-500 uppercase tracking-widest">{{ $item->data_efetiva->translatedFormat('D') }}</span>
            </div>
        </td>
        <td class="px-6 py-4" data-label="Descrição">
            <div class="flex flex-col">
                <span class="text-sm font-medium text-slate-200">
                    {{ $item->descricao }}
                    @if ($item->meta_parcela)
                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black bg-white/5 text-slate-400 border border-white/5">
                            {{ $item->meta_parcela }}
                        </span>
                    @endif
                </span>
                <span class="text-xs text-slate-500">{{ $item->conta->nome }}</span>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black {{ $isCredito ? 'text-emerald-400' : 'text-rose-400' }}"
            data-label="Valor">
            {{ $isCredito ? '+' : '' }} R$ {{ number_format($item->valor, 2, ',', '.') }}
        </td>

        <td class="px-6 py-4 whitespace-nowrap text-sm text-right border-l border-white/5" data-label="Saldo Real">
            @if ($ultimoDoDia)
                <span class="font-bold {{ $item->saldo_acumulado_efetivado >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    R$ {{ number_format($item->saldo_acumulado_efetivado, 2, ',', '.') }}
                </span>
            @else
                <span class="text-slate-700">--</span>
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-right" data-label="Saldo Prev.">
            @if ($ultimoDoDia)
                <span class="font-medium {{ $item->saldo_acumulado_previsto >= 0 ? 'text-slate-300' : 'text-rose-400' }}">
                    R$ {{ number_format($item->saldo_acumulado_previsto, 2, ',', '.') }}
                </span>
            @else
                <span class="text-slate-700">--</span>
            @endif
        </td>

        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium" data-label="Ações">
            <div class="flex justify-center items-center gap-2">
                @if ($item->tipo === 'projetado')
                    @if ($item->status === 'pendente')
                        <button type="button" data-acao="paguei-agora"
                            class="p-2 bg-violet-500/20 text-violet-300 rounded-lg hover:bg-violet-500 hover:text-white transition shadow-lg border border-violet-500/20"
                            title="{{ $isCredito ? 'Confirma e recebe na data atual' : 'Confirma e paga na data atual' }}">
                            <i class="fa-solid fa-bolt text-xs"></i>
                        </button>
                        <button type="button" data-acao="confirmar"
                            class="p-2 bg-amber-500/20 text-amber-400 rounded-lg hover:bg-amber-500 hover:text-white transition shadow-lg border border-amber-500/20"
                            title="Confirmar valor e data">
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                        </button>
                    @elseif($item->status === 'confirmado')
                        <button type="button" data-acao="efetivar"
                            class="p-2 bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-white transition shadow-lg border border-emerald-500/20"
                            title="Pagar / Efetivar">
                            <i class="fa-solid fa-check text-xs"></i>
                        </button>
                        <button type="button" data-acao="confirmar" class="text-slate-500 hover:text-slate-300 transition"
                            title="Editar valor e data">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                    @endif
                @else
                    <div class="px-3 py-1 bg-emerald-500/10 text-emerald-400 rounded-full elf-title text-[8px] flex items-center gap-1 border border-emerald-500/20">
                        <i class="fa-solid fa-check-double text-[8px]"></i>
                        Efetivado
                    </div>
                    <button type="button" data-acao="estornar"
                        class="p-2 bg-rose-500/20 text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white transition shadow-lg border border-rose-500/20"
                        title="Estornar (desfazer efetivação)">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-6 py-20 text-center">
            <div class="flex flex-col items-center gap-4">
                <i class="fa-solid fa-scroll text-5xl text-slate-800"></i>
                <span class="text-slate-500 font-medium italic">Nenhum lançamento encontrado em seus registros.</span>
            </div>
        </td>
    </tr>
@endforelse
