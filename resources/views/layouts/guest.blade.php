<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-300 antialiased">
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-spigo-dark">
    <div>
        <a href="/" wire:navigate>
            <img src="//spigo.net/manual/Spigo.Net_Marcadagua 2_Colorido.png" alt="Spigo.Net Logo" class="w-24 h-auto"
                 onerror="this.onerror=null; this.src='https://placehold.co/200x80/322C3A/FFFFFF?text=Spigo.Net';">
        </a>
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white/5 shadow-lg shadow-spigo-lime/10 overflow-hidden sm:rounded-lg">
        {{ $slot }}
    </div>
</div>
</body>
</html>
