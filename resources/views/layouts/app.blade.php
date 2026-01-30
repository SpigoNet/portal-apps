@props(['moduleName' => '', 'appId' => null])

    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Manifest Dinâmico -->
    @if($attributes->get('module-id'))
        @php($app = (new \App\Services\ModuleService($attributes->get('module-id')))->getCurrentApp())
        <title>{{ config('app.name', 'Spigo Apps') }} {{ $app['title'] ? '- ' . $app['title'] : '' }}</title>
        <link rel="manifest" href="{{ route('pwa.manifest', ['id' => $attributes->get('module-id')]) }}">
        <meta name="theme-color" content="#ccf381"> {{-- Cor padrão Lime --}}
        <!-- Icones PWA Dinâmicos -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ $app['icon'] ?? '' }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ $app['icon'] ?? '' }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ $app['icon'] ?? '' }}">

    @else
        <title>{{ config('app.name', 'Spigo Apps') }}</title>
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-spigo-dark">
    {{-- Componente de Navegação --}}
    <livewire:layout.navigation
        :module-id="$attributes->get('module-id') ?? null"
        :module-name="$moduleName"
        :module-home-route="$attributes->get('module-home-route') ?? ''"
        :module-icon="$attributes->get('module-icon') ?? ''"
        :module-menu="$attributes->get('module-menu') ?? ''"
        :header="$attributes->get('header') ?? ''"
    />

    {{-- Conteúdo Principal --}}
    <main>
        {{ $slot }}
    </main>
</div>
</body>
</html>
