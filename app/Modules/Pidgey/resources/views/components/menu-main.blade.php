<div class="flex items-center gap-1 whitespace-nowrap">
    <a href="{{ route('pidgey.agendamentos.index') }}"
        class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black text-gray-300 hover:text-white uppercase tracking-widest bg-white/5 hover:bg-white/10 rounded-md transition-all border border-transparent hover:border-white/10 {{ request()->routeIs('pidgey.agendamentos.*') ? 'text-white border-white/20' : '' }}">
        <i class="fa-solid fa-paper-plane text-amber-400"></i>
        <span>Agendamentos</span>
    </a>
</div>
