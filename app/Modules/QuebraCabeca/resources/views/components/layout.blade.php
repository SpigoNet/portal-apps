<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quebra-Cabeça</title>
    <link rel="icon" type="image/png" href="{{ asset('images/apps/quebra-cabeca.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-slate-900 text-slate-100 min-h-screen">
    {{ $slot }}
</body>
</html>
