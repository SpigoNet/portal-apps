@php
use Illuminate\Support\Facades\Auth;
$configId = Auth::check() ? session('dspace_config_id_' . Auth::id()) : null;
@endphp

@if($configId)
    <x-dropdown align="left" width="48">
        <x-slot name="trigger">
            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                {{ __('Editor') }}
            </button>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('dspace-forms.index')">
                Dashboard
            </x-dropdown-link>
            <x-dropdown-link :href="route('dspace-forms.forms.index')">
                Formulários
            </x-dropdown-link>
            <x-dropdown-link :href="route('dspace-forms.value-pairs.index')">
                Listas de Valores
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>

    <x-dropdown align="left" width="48">
        <x-slot name="trigger">
            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                {{ __('Configurações') }}
            </button>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('dspace-forms.form-maps.index')">
                Mapeamentos
            </x-dropdown-link>
            <x-dropdown-link :href="route('dspace-forms.emails.index')">
                E-mails
            </x-dropdown-link>
        </x-slot>
    </x-dropdown>
@endif
