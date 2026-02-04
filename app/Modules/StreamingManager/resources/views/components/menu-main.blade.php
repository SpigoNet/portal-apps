<x-dropdown align="left" width="48">
    <x-slot name="trigger">
        <button
            class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-full transition focus:outline-none focus:ring-2 focus:ring-spigo-lime/50"
            title="Menu Streaming">
            Menu Streaming
        </button>
    </x-slot>
    <x-slot name="content">
        <x-dropdown-link :href="route('streaming-manager.index')">
            Meus Streamings
        </x-dropdown-link>
        <x-dropdown-link :href="route('streaming-manager.create')">
            Novo Streaming
        </x-dropdown-link>
    </x-slot>
</x-dropdown>