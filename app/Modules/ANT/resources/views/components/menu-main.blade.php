@php use App\Modules\ANT\Models\AntConfiguracao; @endphp
<x-dropdown-link :href="route('ant.home')">
    Dashboard
</x-dropdown-link>
<?php
$config = AntConfiguracao::first();
$isAdmin = $config && $config->isAdmin(auth()->user()->email);
?>
@if($isAdmin)
    <x-dropdown-link :href="route('ant.admin.home')">
        Painel Admin
    </x-dropdown-link>
@endif
