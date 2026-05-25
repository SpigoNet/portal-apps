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
        <meta name="theme-color" content="#D9F99D">
        <!-- Icones PWA Dinâmicos -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ $app['icon'] ?? '' }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ $app['icon'] ?? '' }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ $app['icon'] ?? '' }}">

    @else
    <title>{{ config('app.name', 'Spigo Apps') }}</title>
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-background text-on-surface">
    <div class="min-h-screen">
        {{-- Componente de Navegação --}}
        <livewire:layout.navigation :module-id="$attributes->get('module-id') ?? null" :module-name="$moduleName"
            :module-home-route="$attributes->get('module-home-route') ?? ''"
            :module-icon="$attributes->get('module-icon') ?? ''" :module-menu="$attributes->get('module-menu') ?? ''"
            :header="$attributes->get('header') ?? ''" />

        {{-- Conteúdo Principal --}}
        <main class="container-margin">
            {{ $slot }}
        </main>
    </div>
    <footer class="text-center py-4 text-on-surface-variant">
        <p class="body-sm">Último deploy:
            @if(file_exists(storage_path('app/deploy_time.txt')))
                {{ file_get_contents(storage_path('app/deploy_time.txt')) }}
            @else
                Data desconhecida
            @endif
        </p>
    </footer>
</body>

</html>