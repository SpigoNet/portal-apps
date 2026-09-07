@props(['title' => 'Yomi', 'active' => null])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | Sua jornada em mangás</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/yomi/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/apps/yomi.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apps/yomi.png') }}">
    <meta name="theme-color" content="#0e0e13">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/yomi.css'])
</head>
<body class="yomi-body">
    <div class="yomi-shell">
        <aside class="yomi-sidebar">
            <a class="brand" href="{{ route('yomi.index') }}"><img class="brand-logo" src="{{ asset('images/yomi/logo.png') }}" alt="Logotipo Yomi"><span class="brand-word"><strong>Yomi</strong><small>SUA JORNADA EM MANGÁS</small></span></a>
            <nav class="side-nav" aria-label="Navegação principal">
                <span class="nav-label">MENU PRINCIPAL</span>
                <a class="{{ request()->routeIs('yomi.index') ? 'active' : '' }}" href="{{ route('yomi.index') }}"><x-yomi::icon icon="inicio" /> Início</a>
                <a class="{{ request()->routeIs('yomi.library') ? 'active' : '' }}" href="{{ route('yomi.library') }}"><x-yomi::icon icon="biblioteca" /> Minha Biblioteca</a>
                <a class="{{ request()->routeIs('yomi.discover') ? 'active' : '' }}" href="{{ route('yomi.discover') }}"><x-yomi::icon icon="explorar" /> Explorar</a>
                <a class="{{ request()->routeIs('yomi.stats') ? 'active' : '' }}" href="{{ route('yomi.stats') }}"><x-yomi::icon icon="estatisticas" /> Estatísticas</a>
                <span class="nav-label nav-label-spaced">CONTINUAR LENDO</span>
                <a class="continue-link" href="{{ route('yomi.library', ['status' => 'lendo']) }}"><x-yomi::icon icon="proxima-leitura" />{{ $active?->manga?->titulo ?? 'Sua próxima leitura' }}<small>{{ $active?->ultimo_capitulo_lido ? 'Cap. '.$active->ultimo_capitulo_lido : 'Comece a registrar' }}</small><i></i></a>
                <span class="nav-label nav-label-spaced">GERAL</span>
                <a href="#"><x-yomi::icon icon="notificacoes" /> Notificações <b class="nav-count">3</b></a>
                @if(auth()->id() === (int) config('yomi.owner_user_id', 1))
                    <a class="{{ request()->routeIs('yomi.settings', 'yomi.settings.update') ? 'active' : '' }}" href="{{ route('yomi.settings') }}"><x-yomi::icon icon="configuracoes" /> Configurações</a>
                @endif
            </nav>
            <div class="profile-chip"><span class="avatar">{{ mb_substr(auth()->user()->name ?? 'Y', 0, 1) }}</span><span><strong>{{ auth()->user()->name ?? 'Leitor' }}</strong><small>Leitor Yomi</small></span><b>Lv. 14</b></div>
        </aside>
        <main class="yomi-main">
            <header class="topbar"><label class="search"><span>⌕</span><input type="search" placeholder="Buscar mangás, autores ou gêneros..."><kbd>⌘K</kbd></label><div class="top-actions"><button aria-label="Notificações"><x-yomi::icon icon="notificacoes" /><i></i></button><span class="mini-avatar">{{ mb_substr(auth()->user()->name ?? 'Y', 0, 1) }}</span></div></header>
            @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @if(session('error'))<div class="flash" style="background:var(--red);color:#230c0e">{{ session('error') }}</div>@endif
            <div class="yomi-content">{{ $slot }}</div>
        </main>
    </div>
</body>
</html>
