<x-app-layout
    :module-id="4"
    :module-menu="view('ANT::components.menu-main')"
>

    @if(isset($header))
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endif

    {{ $slot }}

</x-app-layout>
