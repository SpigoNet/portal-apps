@props(['titulo' => 'Cardápio', 'menu' => true])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo }} · Cardápio</title>
    <x-pwa-head start-link="/cardapio" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --cd-bg: #F4F3EE; --cd-surface: #fff; --cd-ink: #1C211C; --cd-muted: #5E665F; --cd-line: #E3E1D8;
            --cd-tomate: #D6402A; --cd-tomate-esc: #B8331F; --cd-tomate-suave: #FBE6E0;
            --cd-ervas: #2E6B4C; --cd-ervas-suave: #E1EEE5; --cd-gema: #F2B33D; --cd-gema-suave: #FCF0D2;
        }
        html { background: var(--cd-bg); }
        body { font-family: 'Figtree', system-ui, sans-serif; color: var(--cd-ink); background: var(--cd-bg); -webkit-tap-highlight-color: transparent; }
        h1, h2, h3 { font-family: 'Bricolage Grotesque', 'Figtree', sans-serif; letter-spacing: -0.02em; }
        h1 { font-weight: 800; letter-spacing: -0.03em; }
        a { text-decoration: none; }

        .cd-card { background: var(--cd-surface); border: 1px solid var(--cd-line); border-radius: 1.25rem; padding: 1.25rem; }
        .cd-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; background: var(--cd-tomate); color: #fff; font-weight: 700; border-radius: .9rem; padding: .65rem 1.2rem; min-height: 2.75rem; transition: background .15s; }
        .cd-btn:hover { background: var(--cd-tomate-esc); }
        .cd-btn-sec { background: #fff; color: var(--cd-ink); border: 1px solid var(--cd-line); }
        .cd-btn-sec:hover { background: var(--cd-bg); }
        .cd-btn-perigo { background: var(--cd-tomate-suave); color: #A92E1B; }
        .cd-btn-perigo:hover { background: #F6D3CA; }
        .cd-input { width: 100%; border: 1.5px solid var(--cd-line); border-radius: .8rem; padding: .55rem .85rem; background: #fff; color: var(--cd-ink); min-height: 2.75rem; }
        .cd-input:focus { outline: 2px solid var(--cd-tomate); outline-offset: 1px; border-color: var(--cd-tomate); }
        .cd-label { display: block; font-size: .85rem; font-weight: 700; margin-bottom: .25rem; }
        .cd-chip { display: inline-block; font-size: .75rem; font-weight: 700; padding: .15rem .7rem; border-radius: 999px; background: var(--cd-ervas-suave); color: var(--cd-ervas); }
        .cd-chip-aviso { background: var(--cd-gema-suave); color: #7A5200; }
        .cd-linha-rotulo { font-weight: 700; width: 9rem; flex-shrink: 0; }
        input[type=checkbox], input[type=radio] { accent-color: var(--cd-ervas); }

        /* Cores herdadas das telas antigas, remapeadas para a nova paleta. */
        .cd-app .bg-green-50 { background-color: var(--cd-ervas-suave); }
        .cd-app .bg-green-100 { background-color: var(--cd-ervas-suave); }
        .cd-app .bg-green-600, .cd-app .bg-green-800 { background-color: var(--cd-ervas); }
        .cd-app .text-green-700, .cd-app .text-green-800, .cd-app .text-green-900 { color: var(--cd-ervas); }
        .cd-app .text-amber-800 { color: #7A5200; }
        .cd-app .text-red-700, .cd-app .text-red-900 { color: #A92E1B; }
        .cd-app .bg-red-100 { background-color: var(--cd-tomate-suave); }
        .cd-app .border-green-100, .cd-app .divide-green-100 > :not([hidden]) ~ :not([hidden]) { border-color: var(--cd-line); }
        .cd-app .border-green-500, .cd-app .border-green-600 { border-color: var(--cd-ervas); }

        /* Desktop: barra lateral fixa. */
        .cd-lateral { display: none; }
        .cd-topo, .cd-tabbar { display: none; }
        .cd-nav-i { display: flex; align-items: center; gap: .75rem; height: 2.75rem; padding: 0 .75rem; border-radius: .75rem; font-weight: 600; font-size: .95rem; color: var(--cd-ink); }
        .cd-nav-i:hover { background: #ECEAE2; }
        .cd-nav-i.ativo { background: var(--cd-ink); color: #fff; font-weight: 700; }
        .cd-nav-i svg, .cd-tab svg { flex: none; }
        @media (min-width: 768px) {
            .cd-lateral { display: flex; position: fixed; inset: 0 auto 0 0; width: 15.5rem; flex-direction: column; gap: 1.25rem; padding: 1.5rem 1rem; background: #FBFAF7; border-right: 1px solid var(--cd-line); overflow-y: auto; z-index: 20; }
            .cd-conteudo { margin-left: 15.5rem; }
            .cd-conteudo main { padding-left: 2.5rem; padding-right: 2.5rem; }
        }

        /* Celular: cabeçalho enxuto, barra inferior, alvos ≥ 44px, inputs com 16px (evita zoom do iOS). */
        @media (max-width: 767px) {
            body { padding-bottom: calc(5.25rem + env(safe-area-inset-bottom)); }
            .cd-topo { display: flex; align-items: center; gap: .6rem; position: sticky; top: 0; z-index: 20; padding: calc(.6rem + env(safe-area-inset-top)) 1rem .6rem; background: rgba(244,243,238,.92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--cd-line); }
            .cd-btn { min-height: 2.75rem; padding: .65rem 1rem; text-align: center; }
            .cd-input, select.cd-input, textarea.cd-input { font-size: 16px; }
            input[type=checkbox], input[type=radio] { width: 1.25rem; height: 1.25rem; }
            .cd-card { padding: 1rem; border-radius: 1.1rem; }
            .cd-linha-rotulo { width: 100%; }
            .cd-card label { min-height: 2rem; }
            .cd-acoes { flex-direction: column; align-items: stretch; width: 100%; }
            .cd-acoes > *, .cd-acoes form { width: 100%; }
            .cd-acoes form .cd-btn { width: 100%; }
            .cd-fluido { width: 100% !important; max-width: none !important; min-width: 0 !important; }
            .cd-tabbar { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 30; background: #fff; border-top: 1px solid var(--cd-line); padding: .5rem .5rem calc(.5rem + env(safe-area-inset-bottom)); }
            .cd-tab { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; min-height: 3.25rem; font-size: .7rem; font-weight: 700; color: var(--cd-muted); }
            .cd-tab .cd-pilula { display: flex; align-items: center; justify-content: center; width: 3.5rem; height: 1.9rem; border-radius: 999px; }
            .cd-tab.ativo { color: var(--cd-tomate); }
            .cd-tab.ativo .cd-pilula { background: var(--cd-tomate-suave); }
        }
    </style>
</head>
<body class="antialiased min-h-screen cd-app">
    @if ($menu)
        @php
            $ic = [
                'inicio' => '<path d="M3 11l9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
                'semana' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
                'compras' => '<path d="M3 4h2l2.4 11h11l2-8H6"/><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
                'estoque' => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
                'receitas' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5M9 8h6"/>',
                'assistente' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/>',
                'pessoas' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.3-5.5 6.5-5.5s5.9 1.9 6.5 5.5M16 4.8a3.5 3.5 0 0 1 0 6.4M18 14.8c2 .6 3.2 2.2 3.5 4.7"/>',
                'rotina' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
                'historico' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
                'sobras' => '<path d="M4 10h16l-1.5 9a2 2 0 0 1-2 1.7H7.5a2 2 0 0 1-2-1.7z"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
                'agenda' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
                'ingredientes' => '<path d="M12 21c-4 0-7-3-7-7 0-5 7-11 7-11s7 6 7 11c0 4-3 7-7 7z"/>',
                'mcp' => '<path d="M9 3v6M15 3v6M6 9h12v4a6 6 0 0 1-12 0zM12 19v2"/>',
                'telegram' => '<path d="M21 4L3 11l6 2 2 6 3-4 5 4z"/>',
                'mais' => '<circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/>',
            ];
            $itens = [
                'cardapio.index' => ['Início', 'cardapio.index', 'inicio'],
                'cardapio.planejamento.index' => ['Planejamento', 'cardapio.planejamento.*', 'semana'],
                'cardapio.receitas.index' => ['Receitas', 'cardapio.receitas.*', 'receitas'],
                'cardapio.estoque.index' => ['Estoque', 'cardapio.estoque.*', 'estoque'],
                'cardapio.compras.index' => ['Compras', 'cardapio.compras.*', 'compras'],
                'cardapio.assistente.index' => ['Assistente', 'cardapio.assistente.*', 'assistente'],
                'cardapio.pessoas.index' => ['Pessoas', 'cardapio.pessoas.*', 'pessoas'],
                'cardapio.rotina.index' => ['Rotina', 'cardapio.rotina.*', 'rotina'],
                'cardapio.historico.index' => ['Histórico', 'cardapio.historico.*', 'historico'],
                'cardapio.sobras.index' => ['Sobras', 'cardapio.sobras.*', 'sobras'],
                'cardapio.agenda.index' => ['Agenda', 'cardapio.agenda.*', 'agenda'],
                'cardapio.ingredientes.index' => ['Ingredientes', 'cardapio.ingredientes.*', 'ingredientes'],
                'cardapio.mcp.conexao' => ['MCP', 'cardapio.mcp.conexao', 'mcp'],
                'pidgey.telegram.index' => ['Telegram', 'pidgey.telegram.*', 'telegram'],
            ];
            $principais = [
                ['cardapio.index', 'cardapio.index', 'inicio', 'Início'],
                ['cardapio.planejamento.index', 'cardapio.planejamento.*', 'semana', 'Semana'],
                ['cardapio.compras.index', 'cardapio.compras.*', 'compras', 'Compras'],
                ['cardapio.estoque.index', 'cardapio.estoque.*', 'estoque', 'Estoque'],
            ];
            $rotasPrincipais = array_column($principais, 0);
            $emMais = collect($itens)->contains(fn ($i, $r) => ! in_array($r, $rotasPrincipais, true) && request()->routeIs($i[1]));
            $svg = fn (string $nome, int $t = 20) => '<svg width="'.$t.'" height="'.$t.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$ic[$nome].'</svg>';
            $marca = '<svg width="36" height="36" viewBox="0 0 96 96" aria-hidden="true"><rect width="96" height="96" rx="28" fill="#D6402A"/><circle cx="48" cy="50" r="24" fill="none" stroke="#fff" stroke-width="5"/><circle cx="48" cy="50" r="12" fill="#fff"/><path d="M48 26c0-9 6-14 15-14 0 9-6 14-15 14z" fill="#F2B33D"/></svg>';
        @endphp
        <div x-data="{ mais: false }" @keydown.escape.window="mais = false">
            <aside class="cd-lateral" aria-label="Navegação">
                <a href="{{ route('cardapio.index') }}" class="flex items-center gap-3 px-2">
                    {!! $marca !!}
                    <span style="font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:1.5rem;letter-spacing:-0.02em">Cardápio</span>
                </a>
                <nav class="flex flex-col gap-1">
                    @foreach ($itens as $rota => [$rotulo, $padrao, $icone])
                        <a href="{{ route($rota) }}" class="cd-nav-i {{ request()->routeIs($padrao) ? 'ativo' : '' }}">{!! $svg($icone) !!}{{ $rotulo }}</a>
                    @endforeach
                </nav>
            </aside>

            <header class="cd-topo">
                <a href="{{ route('cardapio.index') }}" class="shrink-0">{!! $marca !!}</a>
                <span class="truncate" style="font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:1.25rem">{{ $titulo }}</span>
            </header>

            <nav class="cd-tabbar" aria-label="Navegação">
                @foreach ($principais as [$rota, $padrao, $icone, $rotulo])
                    <a href="{{ route($rota) }}" class="cd-tab {{ request()->routeIs($padrao) ? 'ativo' : '' }}">
                        <span class="cd-pilula">{!! $svg($icone, 22) !!}</span>{{ $rotulo }}
                    </a>
                @endforeach
                <button type="button" @click="mais = true" class="cd-tab {{ $emMais ? 'ativo' : '' }}">
                    <span class="cd-pilula">{!! $svg('mais', 22) !!}</span>Mais
                </button>
            </nav>

            <div x-cloak x-show="mais" class="fixed inset-0 z-40 md:hidden" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-black/40" @click="mais = false" x-transition.opacity></div>
                <div class="absolute left-0 right-0 bottom-0 bg-white rounded-t-3xl p-4 space-y-3"
                     style="padding-bottom: max(1rem, env(safe-area-inset-bottom))"
                     x-show="mais" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0">
                    <div class="flex items-center justify-between">
                        <span style="font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:1.25rem">Mais</span>
                        <button type="button" class="px-3 text-2xl" style="min-height:2.75rem" @click="mais = false" aria-label="Fechar">✕</button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($itens as $rota => [$rotulo, $padrao, $icone])
                            @continue(in_array($rota, $rotasPrincipais, true))
                            <a href="{{ route($rota) }}"
                               class="rounded-2xl px-2 py-3 flex flex-col items-center gap-1 text-center text-sm font-bold"
                               style="{{ request()->routeIs($padrao) ? 'background:#1C211C;color:#fff' : 'background:#F4F3EE;color:#1C211C' }}">{!! $svg($icone, 22) !!}{{ $rotulo }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="{{ $menu ? 'cd-conteudo' : '' }}">
        <main class="max-w-5xl mx-auto px-4 py-4 md:py-8 space-y-4" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom))">
            @if (session('status'))
                <div class="rounded-2xl px-4 py-3 font-semibold" style="background:var(--cd-ervas-suave);color:var(--cd-ervas)">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl px-4 py-3" style="background:var(--cd-tomate-suave);color:#A92E1B">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
