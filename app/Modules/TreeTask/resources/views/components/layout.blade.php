<x-app-layout :module-id="3" module-name="TreeTask" module-home-route="treetask.index" :module-menu="$contextMenu ?? ''">
    {{-- Repassa o header se ele existir --}}
    @if (isset($header))
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endif

    {{-- Conteúdo Principal --}}
    {{ $slot }}

</x-app-layout>
