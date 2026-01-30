<x-dropdown align="left" width="48">
    <x-slot name="trigger">
        <button
            class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-full transition focus:outline-none focus:ring-2 focus:ring-spigo-lime/50"
            title="Menu Principal">
            Menu Principal
        </button>
    </x-slot>
    <x-slot name="content">
        <x-dropdown-link :href="route('mundos-de-mim.perfil.index')">
            Meu Perfil
        </x-dropdown-link>
        <x-dropdown-link :href="route('mundos-de-mim.pessoas.index')">
            Entes Queridos
        </x-dropdown-link>
        <x-dropdown-link :href="route('mundos-de-mim.galeria.index')">
            Minha Galeria
        </x-dropdown-link>
        <x-dropdown-link :href="route('mundos-de-mim.estilos.index')">
            Meus Mundos
        </x-dropdown-link>
    </x-slot>
</x-dropdown>
@can('admin-do-app')
    <x-dropdown align="left" width="48">
        <x-slot name="trigger">
            <button
                class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-full transition focus:outline-none focus:ring-2 focus:ring-spigo-lime/50"
                title="Menu Principal">
                Menu Admin
            </button>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('mundos-de-mim.admin.themes.index')">
                Temas & Estilos
            </x-dropdown-link>
            <x-dropdown-link :href="route('mundos-de-mim.admin.importador.index')">
                Importador
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>

@endcan
