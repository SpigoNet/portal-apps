<x-dropdown-link :href="route('ant.home')">
    Dashboard
</x-dropdown-link>
@can('admin-do-app')
    <x-dropdown-link :href="route('ant.admin.home')">
        Painel Admin
    </x-dropdown-link>
    <x-dropdown-link :href="route('ant.admin.professores.create')">
        Vincular Professor
    </x-dropdown-link>
@endcan