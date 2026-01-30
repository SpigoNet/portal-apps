<x-app-layout
    :module-id="3"
    :module-menu="$contextMenu ?? ''"
>
    {{-- Repassa o header se ele existir --}}
    @if(isset($header))
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endif

    {{-- Conteúdo Principal --}}
    {{ $slot }}

</x-app-layout>
