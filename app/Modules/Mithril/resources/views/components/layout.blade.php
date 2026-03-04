@props(['contextMenu' => null])

@php
    $themes = [
        'mithril' => [
            'bg' => 'bg-[#0f172a]', // Slate 900
            'surface' => 'bg-[#1e293b]/80', // Slate 800 with transparency
            'accent' => 'text-[#e2e8f0]', // Slate 200 (Platinum)
            'border' => 'border-[#334155]', // Slate 700
            'button' => 'bg-[#475569] hover:bg-[#64748b]',
            'css_vars' => [
                '--mithril-primary' => '#cbd5e1', // Light Silver
                '--mithril-secondary' => '#1e293b',
                '--mithril-accent' => '#7dd3fc', // Sky Blue
                '--mithril-glow' => 'rgba(125, 211, 252, 0.3)',
            ],
        ],
        'lorien' => [
            'bg' => 'bg-[#064e3b]', // Emerald 950
            'surface' => 'bg-[#065f46]/80', // Emerald 900
            'accent' => 'text-[#d1fae5]', // Emerald 100
            'border' => 'border-[#047857]', // Emerald 700
            'button' => 'bg-[#059669] hover:bg-[#10b981]',
            'css_vars' => [
                '--mithril-primary' => '#34d399', // Emerald
                '--mithril-secondary' => '#064e3b',
                '--mithril-accent' => '#a7f3d0', // Mint
                '--mithril-glow' => 'rgba(52, 211, 153, 0.2)',
            ],
        ],
        'rivendell' => [
            'bg' => 'bg-[#1e1b4b]', // Indigo 950
            'surface' => 'bg-[#312e81]/80', // Indigo 900
            'accent' => 'text-[#fbbf24]', // Amber 400
            'border' => 'border-[#4338ca]', // Indigo 700
            'button' => 'bg-[#4f46e5] hover:bg-[#6366f1]',
            'css_vars' => [
                '--mithril-primary' => '#818cf8', // Indigo
                '--mithril-secondary' => '#1e1b4b',
                '--mithril-accent' => '#fcd34d', // Gold
                '--mithril-glow' => 'rgba(252, 211, 77, 0.2)',
            ],
        ],
    ];

    $currentThemeKey = session('mithril_theme', 'mithril');
    $theme = $themes[$currentThemeKey] ?? $themes['mithril'];
@endphp

<x-app-layout :module-id="9" :module-menu="view('Mithril::components.menu-main')">
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Marcellus&display=swap"
        rel="stylesheet">

    <style>
        :root {
            @foreach ($theme['css_vars'] as $var => $value)
                {{ $var }}: {{ $value }};
            @endforeach
        }

        .mithril-theme-bg {
            @apply {{ $theme['bg'] }};
            background-image: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.02) 0%, transparent 100%);
        }

        .mithril-theme-surface {
            @apply {{ $theme['surface'] }};
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 6px -1px var(--mithril-glow);
        }

        .mithril-theme-accent {
            @apply {{ $theme['accent'] }};
        }

        .mithril-theme-border {
            @apply {{ $theme['border'] }};
        }

        .mithril-theme-button {
            @apply {{ $theme['button'] }};
        }

        /* Fontes e detalhes élficos */
        .elf-font {
            font-family: 'Marcellus', serif;
            letter-spacing: 0.02em;
        }

        .elf-title {
            font-family: 'Cinzel', serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .elf-border {
            position: relative;
            padding-bottom: 0.5rem;
        }

        .elf-border::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--mithril-accent), transparent);
        }

        /* Botão Premium */
        .btn-elf {
            @apply px-6 py-2 rounded-full font-black text-[10px] uppercase tracking-[0.2em] transition-all duration-300;
            font-family: 'Cinzel', serif;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
        }

        .btn-elf:hover {
            border-color: var(--mithril-accent);
            box-shadow: 0 0 15px var(--mithril-glow);
            transform: translateY(-1px);
        }

        /* Estilo para tabelas no mobile */
        @media (max-width: 768px) {

            .mobile-stack table,
            .mobile-stack thead,
            .mobile-stack tbody,
            .mobile-stack th,
            .mobile-stack td,
            .mobile-stack tr {
                display: block;
            }

            .mobile-stack thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .mobile-stack tr {
                margin-bottom: 1.5rem;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid var(--mithril-border);
                border-radius: 1rem;
                padding: 1rem;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            }

            .mobile-stack td {
                border: none;
                position: relative;
                padding-left: 45%;
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
                text-align: right;
                font-size: 0.875rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }

            .mobile-stack td:last-child {
                border-bottom: none;
            }

            .mobile-stack td:before {
                position: absolute;
                top: 0.75rem;
                left: 0;
                width: 40%;
                padding-right: 10px;
                white-space: nowrap;
                content: attr(data-label);
                text-align: left;
                font-size: 0.7rem;
                font-family: 'Cinzel', serif;
                font-weight: bold;
                color: var(--mithril-accent);
                opacity: 0.8;
            }
        }
    </style>

    <div class="mithril-theme-bg min-h-screen text-slate-100 elf-font">
        @if (isset($header))
            <x-slot name="header">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="elf-border bg-black/10 px-4 py-2 rounded-lg backdrop-blur-sm border border-white/5">
                        {{ $header }}
                    </div>

                    {{-- Seletor de Temas --}}
                    <div
                        class="flex items-center gap-3 bg-black/40 p-1.5 rounded-full border border-white/10 self-start md:self-center">
                        <a href="{{ route('mithril.set-theme', ['theme' => 'mithril']) }}"
                            class="w-7 h-7 rounded-full bg-slate-500 border-2 {{ $currentThemeKey == 'mithril' ? 'border-white scale-110 shadow-[0_0_10px_rgba(255,255,255,0.5)]' : 'border-transparent opacity-40 hover:opacity-100' }} transition-all"
                            title="Mithril (Silver)"></a>
                        <a href="{{ route('mithril.set-theme', ['theme' => 'lorien']) }}"
                            class="w-7 h-7 rounded-full bg-emerald-600 border-2 {{ $currentThemeKey == 'lorien' ? 'border-white scale-110 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'border-transparent opacity-40 hover:opacity-100' }} transition-all"
                            title="Lórien (Forest)"></a>
                        <a href="{{ route('mithril.set-theme', ['theme' => 'rivendell']) }}"
                            class="w-7 h-7 rounded-full bg-indigo-600 border-2 {{ $currentThemeKey == 'rivendell' ? 'border-white scale-110 shadow-[0_0_10px_rgba(99,102,241,0.5)]' : 'border-transparent opacity-40 hover:opacity-100' }} transition-all"
                            title="Rivendell (Autumn)"></a>
                    </div>
                </div>
            </x-slot>
        @endif

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </div>

</x-app-layout>
