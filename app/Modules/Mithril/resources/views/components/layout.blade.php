@props(['contextMenu' => null])

<x-app-layout>
    <x-slot name="moduleName">Mithril</x-slot>
    <x-slot name="moduleHomeRoute">mithril.index</x-slot>
    <x-slot name="moduleIcon">
        🏦
    </x-slot>

    <x-slot name="moduleMenu">

        @include('Mithril::components.menu-main')

        @if(!empty($contextMenu))
            <div class="border-t border-gray-100 dark:border-gray-600 my-1"></div>
            {{ $contextMenu }}
        @endif

    </x-slot>

    @if(isset($header))
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endif

    {{ $slot }}

</x-app-layout>
