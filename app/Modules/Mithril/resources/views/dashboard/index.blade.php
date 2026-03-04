<x-Mithril::layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-4">
                <div class="p-3 mithril-theme-button rounded-full text-white shadow-lg border border-white/20">
                    <i class="fa-solid fa-gem text-xl"></i>
                </div>
                <div>
                    <h2 class="elf-title text-2xl mithril-theme-accent">
                        Mithril Dashboard
                    </h2>
                    <p class="text-[10px] text-slate-400 font-medium uppercase tracking-[0.3em]">Tesouraria de Valfenda
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('mithril.pre-transacoes.create') }}" class="btn-elf text-white">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Novo Lançamento
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-12">
        {{-- Resumo Geral --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @php
                $totalRealHoje = collect($dadosContas)->sum('real_hoje');
                $totalPrevistoFimMes = collect($dadosContas)->sum('previsto_fim_mes');
                $totalCartoes = collect($dadosCartoes)->sum('total_pagar');
                $liquidez = $totalPrevistoFimMes - abs($totalCartoes);
            @endphp

            <div
                class="mithril-theme-surface p-6 rounded-2xl border border-white/5 relative overflow-hidden group transition-all hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="fa-solid fa-coins text-8xl"></i>
                </div>
                <p class="elf-title text-[10px] text-slate-400 mb-1">Saldo Real Hoje</p>
                <h3 class="text-3xl font-black {{ $totalRealHoje >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    R$ {{ number_format($totalRealHoje, 2, ',', '.') }}
                </h3>
                <div class="mt-4 h-0.5 w-full bg-emerald-500/10 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]" style="width: 70%"></div>
                </div>
            </div>

            <div
                class="mithril-theme-surface p-6 rounded-2xl border border-white/5 relative overflow-hidden group transition-all hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="fa-solid fa-chart-line text-8xl"></i>
                </div>
                <p class="elf-title text-[10px] text-slate-400 mb-1">Projeção Fim do Mês</p>
                <h3 class="text-3xl font-black {{ $totalPrevistoFimMes >= 0 ? 'text-sky-300' : 'text-rose-400' }}">
                    R$ {{ number_format($totalPrevistoFimMes, 2, ',', '.') }}
                </h3>
                <div class="mt-4 h-0.5 w-full bg-sky-500/10 rounded-full overflow-hidden">
                    <div class="h-full bg-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.5)]" style="width: 85%"></div>
                </div>
            </div>

            <div
                class="mithril-theme-surface p-6 rounded-2xl border border-white/5 relative overflow-hidden group transition-all hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="fa-solid fa-credit-card text-8xl"></i>
                </div>
                <p class="elf-title text-[10px] text-slate-400 mb-1">Total em Cartões</p>
                <h3 class="text-3xl font-black text-rose-400">
                    R$ {{ number_format(abs($totalCartoes), 2, ',', '.') }}
                </h3>
                <div class="mt-4 h-0.5 w-full bg-rose-500/10 rounded-full overflow-hidden">
                    <div class="h-full bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.5)]" style="width: 40%"></div>
                </div>
            </div>

            <div
                class="mithril-theme-surface p-6 rounded-2xl border border-white/5 relative overflow-hidden group transition-all hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <i class="fa-solid fa-shield-halved text-8xl"></i>
                </div>
                <p class="elf-title text-[10px] text-slate-400 mb-1">Liquidez Projetada</p>
                <h3 class="text-3xl font-black {{ $liquidez >= 0 ? 'text-amber-300' : 'text-rose-400' }}">
                    R$ {{ number_format($liquidez, 2, ',', '.') }}
                </h3>
                <div class="mt-4 h-0.5 w-full bg-amber-500/10 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.5)]" style="width: 60%"></div>
                </div>
            </div>
        </div>

        {{-- Contas --}}
        <div>
            <div class="flex items-center gap-4 mb-8">
                <h3 class="elf-title text-xl mithril-theme-accent">
                    Cofres e Bolsas
                </h3>
                <div class="flex-1 h-px bg-gradient-to-right from-white/10 to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($dadosContas as $conta)
                    <div
                        class="mithril-theme-surface overflow-hidden rounded-3xl border border-white/5 hover:border-white/20 transition-all group">
                        <div class="p-6 bg-white/5 flex justify-between items-center border-b border-white/5">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl mithril-theme-button flex items-center justify-center shadow-lg border border-white/10">
                                    <i class="fa-solid fa-building-columns text-white"></i>
                                </div>
                                <h4 class="elf-title text-sm text-white">{{ $conta['nome'] }}</h4>
                            </div>
                            <a href="{{ route('mithril.lancamentos.index', ['conta_id' => $conta['id']]) }}"
                                class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/10 transition backdrop-blur-md">
                                <i class="fa-solid fa-arrow-up-right-from-square text-xs text-slate-400"></i>
                            </a>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex justify-between items-end">
                                <span class="elf-title text-[9px] text-slate-500">Saldo Atual</span>
                                <span
                                    class="{{ $conta['real_hoje'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }} font-black text-2xl tracking-tighter">
                                    R$ {{ number_format($conta['real_hoje'], 2, ',', '.') }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-6 border-t border-white/5">
                                <div>
                                    <p class="elf-title text-[8px] text-slate-500 mb-1">Previsto Hoje</p>
                                    <p
                                        class="font-bold text-sm {{ $conta['previsto_hoje'] >= 0 ? 'text-slate-200' : 'text-rose-400' }}">
                                        R$ {{ number_format($conta['previsto_hoje'], 2, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="elf-title text-[8px] text-slate-500 mb-1">Fim do Mês</p>
                                    <p
                                        class="font-bold text-sm {{ $conta['previsto_fim_mes'] >= 0 ? 'text-slate-200' : 'text-rose-400' }}">
                                        R$ {{ number_format($conta['previsto_fim_mes'], 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-full p-12 mithril-theme-surface rounded-3xl text-center border-2 border-dashed border-white/5">
                        <i class="fa-solid fa-folder-open text-4xl text-slate-700 mb-4 block"></i>
                        <p class="text-slate-500 italic">Nenhum cofre encontrado em suas terras.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Cartões --}}
        <div>
            <div class="flex items-center gap-4 mb-8">
                <h3 class="elf-title text-xl text-rose-400">
                    Créditos de Ferro
                </h3>
                <div class="flex-1 h-px bg-gradient-to-right from-rose-500/20 to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($dadosCartoes as $cartao)
                    <div
                        class="mithril-theme-surface overflow-hidden rounded-3xl border border-rose-500/10 hover:border-rose-500/30 transition-all relative group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-30 transition-opacity">
                            <i class="fa-solid fa-bolt text-rose-500 text-4xl"></i>
                        </div>
                        <div class="p-6 bg-rose-500/5 flex justify-between items-center border-b border-white/5">
                            <h4 class="elf-title text-sm text-white">{{ $cartao['nome'] }}</h4>
                            <span
                                class="elf-title text-[8px] px-3 py-1 bg-rose-500/20 text-rose-400 rounded-full">Dívida</span>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex justify-between items-center">
                                <span class="elf-title text-[9px] text-slate-500">Fatura Aberta</span>
                                <span class="text-rose-400 font-black text-2xl tracking-tighter">
                                    R$ {{ number_format(abs($cartao['fatura_aberta']), 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center pt-6 border-t border-white/5">
                                <span class="elf-title text-[9px] text-slate-400">Total Devido</span>
                                <span class="text-rose-500 font-black text-lg">
                                    R$ {{ number_format(abs($cartao['total_pagar']), 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4 bg-black/30 text-center">
                            <a href="{{ route('mithril.faturas.show', $cartao['id']) }}"
                                class="elf-title text-[9px] text-rose-400 hover:text-rose-300 flex items-center justify-center gap-2 transition-all">
                                Consultar Pergaminho
                                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-full p-12 mithril-theme-surface rounded-3xl text-center border-2 border-dashed border-white/5">
                        <p class="text-slate-500 italic">Nenhuma dívida de ferro registrada.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-mithril::layout>
