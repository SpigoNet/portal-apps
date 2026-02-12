<x-app-layout :module-id="2" :module-menu="view('DspaceForms::components.menu-main')">
    <x-slot name="moduleMenu">
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