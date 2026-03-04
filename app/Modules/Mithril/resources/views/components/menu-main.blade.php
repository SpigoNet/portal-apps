<div class="flex items-center gap-1">
    {{-- Grupo 1: Resumo --}}
    <x-dropdown align="left" width="56">
        <x-slot name="trigger">
            <button class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black text-gray-400 hover:text-white uppercase tracking-widest bg-white/5 hover:bg-white/10 rounded-md transition-all border border-transparent hover:border-white/10 group">
                <i class="fa-solid fa-chart-pie text-spigo-lime group-hover:scale-110 transition-transform"></i>
                <span>Resumo</span>
                <i class="fa-solid fa-chevron-down text-[8px] opacity-40"></i>
            </button>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('mithril.index')" :active="request()->routeIs('mithril.index')">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-gauge-high w-4 text-blue-500"></i>
                    <span>Dashboard Geral</span>
                </div>
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>

    {{-- Grupo 2: Operações --}}
    <x-dropdown align="left" width="56">
        <x-slot name="trigger">
            <button class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black text-gray-400 hover:text-white uppercase tracking-widest bg-white/5 hover:bg-white/10 rounded-md transition-all border border-transparent hover:border-white/10 group">
                <i class="fa-solid fa-money-bill-transfer text-spigo-lime group-hover:scale-110 transition-transform"></i>
                <span>Operações</span>
                <i class="fa-solid fa-chevron-down text-[8px] opacity-40"></i>
            </button>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('mithril.lancamentos.index')" :active="request()->routeIs('mithril.lancamentos.*')">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-list-check w-4 text-emerald-500"></i>
                    <span>Fluxo de Caixa</span>
                </div>
            </x-dropdown-link>
            <div class="flex flex-col">
                <x-dropdown-link :href="route('mithril.pre-transacoes.index')" :active="request()->routeIs('mithril.pre-transacoes.index')">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-plus w-4 text-amber-500"></i>
                        <span>Planejamento Mensal</span>
                    </div>
                </x-dropdown-link>
                <x-dropdown-link :href="route('mithril.pre-transacoes.create')" class="pl-8 text-[10px] text-gray-500 hover:text-indigo-600">
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>Novo Planejamento</span>
                    </div>
                </x-dropdown-link>
            </div>
            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
            <x-dropdown-link :href="route('mithril.fechamentos.index')" :active="request()->routeIs('mithril.fechamentos.*')">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar w-4 text-purple-500"></i>
                    <span>Fechamentos de Mês</span>
                </div>
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>

    {{-- Grupo 3: Configurações --}}
    <x-dropdown align="left" width="56">
        <x-slot name="trigger">
            <button class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black text-gray-400 hover:text-white uppercase tracking-widest bg-white/5 hover:bg-white/10 rounded-md transition-all border border-transparent hover:border-white/10 group">
                <i class="fa-solid fa-sliders text-spigo-lime group-hover:scale-110 transition-transform"></i>
                <span>Gestão</span>
                <i class="fa-solid fa-chevron-down text-[8px] opacity-40"></i>
            </button>
        </x-slot>
        <x-slot name="content">
            <div class="px-4 py-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50/50 dark:bg-gray-800/50">
                Em breve
            </div>
            <x-dropdown-link href="#" class="opacity-50 cursor-not-allowed">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-credit-card w-4"></i>
                    <span>Cartões de Crédito</span>
                </div>
            </x-dropdown-link>
            <x-dropdown-link href="#" class="opacity-50 cursor-not-allowed">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-building-columns w-4"></i>
                    <span>Contas Bancárias</span>
                </div>
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>
</div>
