<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Vocabulário Controlado') — RIC-CPS</title>
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        /* Força tema claro para o VocabularioControlado */
        :root {
            --color-background: #ffffff;
            --color-on-background: #1a1a1a;
            --color-surface: #ffffff;
            --color-surface-dim: #f5f5f5;
            --color-surface-bright: #ffffff;
            --color-surface-container-lowest: #fafafa;
            --color-surface-container-low: #f5f5f5;
            --color-surface-container: #eeeeee;
            --color-surface-container-high: #e8e8e8;
            --color-surface-container-highest: #ddd ddd;
            --color-on-surface: #1a1a1a;
            --color-on-surface-variant: #333333;
            --color-outline: #999999;
            --color-outline-variant: #cccccc;
        }
        
        body {
            --tw-bg-opacity: 1 !important;
            background-color: rgb(255 255 255 / var(--tw-bg-opacity)) !important;
            color: rgb(26 26 26 / var(--tw-text-opacity, 1)) !important;
        }
        
        .bg-white { background-color: #ffffff !important; }
        .bg-surface { background-color: #ffffff !important; }
        .bg-surface-dim { background-color: #f5f5f5 !important; }
        .bg-surface-container { background-color: #eeeeee !important; }
        .bg-surface-container-low { background-color: #f5f5f5 !important; }
        .bg-surface-container-high { background-color: #e8e8e8 !important; }
        .bg-surface-container-lowest { background-color: #fafafa !important; }
        .bg-surface-container-highest { background-color: #dddddd !important; }
        .bg-background { background-color: #ffffff !important; }
        
        .text-on-surface { color: #1a1a1a !important; }
        .text-on-background { color: #1a1a1a !important; }
        .text-on-surface-variant { color: #333333 !important; }
        
        /* Força texto escuro em links e elementos interativos */
        a { color: #0066cc !important; }
        a:visited { color: #663399 !important; }
        a:hover { color: #0052a3 !important; }
        
        button, input[type="button"], input[type="submit"] {
            background-color: #e8e8e8 !important;
            color: #1a1a1a !important;
            border-color: #999999 !important;
        }
        
        input, textarea, select {
            background-color: #ffffff !important;
            color: #1a1a1a !important;
            border-color: #cccccc !important;
        }
        
        /* Garante contraste em tables */
        table { color: #1a1a1a !important; background-color: #ffffff !important; }
        thead { background-color: #e8e8e8 !important; color: #1a1a1a !important; }
        tbody tr:nth-child(odd) { background-color: #f5f5f5 !important; }
        tbody tr:nth-child(even) { background-color: #ffffff !important; }
        
        /* Força cores de texto Tailwind para escuro */
        .text-gray-600, .text-gray-500, .text-gray-400, .text-gray-700 { color: #1a1a1a !important; }
        .text-blue-800, .text-blue-700, .text-blue-600, .text-blue-500 { color: #0052a3 !important; }
        .text-white { color: #1a1a1a !important; background-color: #e8e8e8 !important; }
        .text-slate-700, .text-slate-600 { color: #1a1a1a !important; }
        
        /* Força cores de fundo para claro */
        .bg-gray-100, .bg-gray-50 { background-color: #f5f5f5 !important; }
        .bg-gray-200 { background-color: #eeeeee !important; }
        .bg-blue-700, .bg-blue-800 { background-color: #0052a3 !important; }
        .bg-slate-50 { background-color: #f5f5f5 !important; }
        .bg-slate-100 { background-color: #eeeeee !important; }
        
        /* Borders claros */
        .border-gray-300, .border-gray-200 { border-color: #cccccc !important; }
        .border-blue-500 { border-color: #0066cc !important; }
        
        /* Focus states com contraste bom */
        .focus\:ring-blue-500:focus { --tw-ring-color: #0052a3 !important; }
        .focus\:outline-none:focus { outline: 2px solid #0052a3 !important; }
    </style>
</head>
<body class="bg-white min-h-screen" style="color: #1a1a1a;">

@unless($semMenu ?? false)
<header class="bg-surface-container-high shadow" style="background-color: #e8e8e8 !important; color: #1a1a1a !important;">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" style="color: #4CAF50;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        <span class="font-headline font-semibold text-lg tracking-wide" style="color: #1a1a1a;">Vocabulário Controlado — RIC-CPS</span>
    </div>
</header>
@endunless

<main class="max-w-5xl mx-auto px-4 py-8">
    @yield('content')
</main>

</body>
</html>
