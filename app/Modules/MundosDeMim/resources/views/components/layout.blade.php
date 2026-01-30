<x-app-layout
    :module-id="10"
    :module-menu="view('MundosDeMim::components.menu-main')"
>
    {{-- Repassa o header se ele existir --}}
    @if(isset($header))
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endif


    {{ $slot }}

</x-app-layout>

