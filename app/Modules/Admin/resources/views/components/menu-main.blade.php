<x-nav-link :href="route('admin.apps.index')" :active="request()->routeIs('admin.apps.*')">
    {{ __('Aplicativos') }}
</x-nav-link>

<x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
    {{ __('Usuários') }}
</x-nav-link>

<x-nav-link :href="route('admin.packages.index')" :active="request()->routeIs('admin.packages.*')">
    {{ __('Pacotes') }}
</x-nav-link>

<x-dropdown align="left" width="48">
    <x-slot name="trigger">
        <x-nav-link :active="request()->routeIs('admin.ai.*')" class="cursor-pointer">
            {{ __('IA') }}
        </x-nav-link>
    </x-slot>
    <x-slot name="content">
        <x-dropdown-link :href="route('admin.ai.provedores.index')">
            Provedores
        </x-dropdown-link>
    </x-slot>
</x-dropdown>

<x-nav-link :href="route('admin.icon-generator')" :active="request()->routeIs('admin.icon-generator')">
    {{ __('Gerador de Ícones') }}
</x-nav-link>
