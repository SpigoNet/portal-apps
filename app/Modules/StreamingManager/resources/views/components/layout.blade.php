<x-app-layout :module-id="11" :module-menu="view('StreamingManager::components.menu-main')">
    {{-- Repassa o header se ele existir --}}
    @if(isset($header))
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endif


    {{ $slot }}

</x-app-layout>