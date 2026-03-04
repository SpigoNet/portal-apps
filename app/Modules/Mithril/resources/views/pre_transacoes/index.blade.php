<x-Mithril::layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-3">
                <div class="p-2 mithril-theme-button rounded-lg text-white shadow-lg">
                    <i class="fa-solid fa-hourglass-start text-xl"></i>
                </div>
                <div>
                    <h2 class="elf-title text-xl text-white leading-tight">
                        Planejamento de Gastos
                        <span class="block text-[10px] text-slate-400 font-medium uppercase tracking-[0.2em]">Assinaturas
                            e Contas Recorrentes</span>
                    </h2>
                </div>
            </div>
            <a href="{{ route('mithril.pre-transacoes.create') }}" class="btn-elf text-white">
                <i class="fa-solid fa-plus mr-2"></i>
                Novo Planejamento
            </a>
        </div>
    </x-slot>


    <div class="py-8">
        <div class="mithril-theme-surface rounded-2xl border border-white/5 overflow-hidden mobile-stack">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/5">
                    <thead>
                        <tr class="bg-black/20">
                            <th class="px-6 py-4 text-left elf-title text-[9px] text-slate-400">Dia</th>
                            <th class="px-6 py-4 text-left elf-title text-[9px] text-slate-400">Descrição / Conta</th>
                            <th class="px-6 py-4 text-right elf-title text-[9px] text-slate-400">Valor</th>
                            <th class="px-6 py-4 text-center elf-title text-[9px] text-slate-400">Tipo / Parcelas</th>
                            <th class="px-6 py-4 text-center elf-title text-[9px] text-slate-400">Status</th>
                            <th class="px-6 py-4 text-center elf-title text-[9px] text-slate-400">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($preTransacoes as $pt)
                            <tr
                                class="hover:bg-white/5 transition duration-150 {{ !$pt->ativa ? 'opacity-30 grayscale' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap" data-label="Dia">
                                    <span
                                        class="text-sm font-black text-slate-500">#{{ str_pad($pt->dia_vencimento, 2, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-6 py-4" data-label="Descrição">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-200">{{ $pt->descricao }}</span>
                                        <span class="text-xs text-slate-500">{{ $pt->conta->nome }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black {{ $pt->valor_parcela >= 0 ? 'text-emerald-400' : 'text-rose-400' }}"
                                    data-label="Valor">
                                    R$ {{ number_format(abs($pt->valor_parcela), 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Tipo">
                                    @if ($pt->tipo == 'parcelada')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full elf-title text-[8px] bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                            <i class="fa-solid fa-layer-group mr-1.5 text-[7px]"></i>
                                            Parcelado ({{ $pt->total_parcelas }}x)
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full elf-title text-[8px] bg-mithril-accent/10 text-mithril-accent border border-mithril-accent/20">
                                            <i class="fa-solid fa-rotate-right mr-1.5 text-[7px]"></i>
                                            Recorrente
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Status">
                                    <a href="{{ route('mithril.pre-transacoes.toggle', $pt->id) }}"
                                        class="inline-flex items-center px-3 py-1 rounded-full elf-title text-[8px] {{ $pt->ativa ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border border-slate-500/20' }} transition hover:scale-105">
                                        <i
                                            class="fa-solid {{ $pt->ativa ? 'fa-play' : 'fa-pause' }} mr-1.5 text-[7px]"></i>
                                        {{ $pt->ativa ? 'Ativo' : 'Pausado' }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Ações">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('mithril.pre-transacoes.edit', $pt->id) }}"
                                            class="p-2 bg-white/5 text-slate-400 rounded-lg hover:bg-mithril-accent hover:text-white transition shadow-lg border border-white/5">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>
                                        <form action="{{ route('mithril.pre-transacoes.destroy', $pt->id) }}"
                                            method="POST" class="inline"
                                            onsubmit="return confirm('Excluir permanentemente este planejamento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 bg-white/5 text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white transition shadow-lg border border-white/5">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-Mithril::layout>
