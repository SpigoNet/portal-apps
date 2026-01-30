@props(['contextMenu' => null])

<x-app-layout
    :module-id="9"
    :module-menu="view('Mithril::components.menu-main')"
>

    @if(isset($header))
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endif

    {{ $slot }}

</x-app-layout>
