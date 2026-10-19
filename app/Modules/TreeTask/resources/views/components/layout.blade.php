@props([
    'titulo' => 'TreeTask',
    'ativo' => 'projetos',
    'bare' => false,
    'fab' => null,
    'quill' => false,
])
@php
    $usuario = auth()->user();
    $itensMenu = [
        'projetos' => ['rota' => 'treetask.index', 'rotulo' => 'Projetos', 'icone' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>', 'curto' => 'Projetos'],
        'kanban' => ['rota' => 'treetask.kanban', 'rotulo' => 'Kanban', 'icone' => '<rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="10" y="4" width="5" height="10" rx="1.5"/><rect x="17" y="4" width="4" height="13" rx="1.5"/>', 'curto' => 'Kanban'],
        'bomdia' => ['rota' => 'treetask.good_morning', 'rotulo' => 'Bom dia', 'icone' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>', 'curto' => 'Bom dia'],
        'foco' => ['rota' => 'treetask.focus.index', 'rotulo' => 'Modo foco', 'icone' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>', 'curto' => 'Foco'],
        'ia' => ['rota' => 'treetask.ai.index', 'rotulo' => 'Assistente IA', 'icone' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>', 'curto' => 'IA'],
    ];
    $arvore = '<path d="M12 21v-8M12 13c-4 0-6-2-6-5M12 13c4 0 6-2 6-5M6 8V4M18 8V4M12 13V3"/><circle cx="12" cy="21" r="1.7" fill="#C9E86B" stroke="none"/>';
    $grade = '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>';
    $portalApp = \App\Models\PortalApp::find(3);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Spigo Apps') }} - TreeTask · {{ $titulo }}</title>
    <x-pwa-head :portal-app="$portalApp" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    @if($quill)
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    @endif
    @include('TreeTask::partials.estilos')
    @livewireStyles
</head>
<body class="tt-body" x-data="{ mods: false }" @keydown.escape.window="mods = false">
@if($bare)
    {{ $slot }}
@else
    <div class="tt-shell">
        {{-- Barra lateral (desktop) --}}
        <aside class="tt-side">
            <a class="tt-brand" href="{{ route('treetask.index') }}" aria-label="TreeTask">
                <span class="tt-brand-icone"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $arvore !!}</svg></span>
                <span class="tt-brand-nome">TreeTask</span>
            </a>

            <button type="button" class="tt-modbtn" @click="mods = !mods" :aria-expanded="mods">
                <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true">{!! $grade !!}</svg>
                Módulos
                <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true" style="margin-left:auto"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            <nav class="tt-nav" aria-label="TreeTask">
                @foreach($itensMenu as $chave => $item)
                    <a href="{{ route($item['rota']) }}" @class(['on' => $ativo === $chave]) @if($ativo === $chave) aria-current="page" @endif>
                        <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true">{!! $item['icone'] !!}</svg>{{ $item['rotulo'] }}
                    </a>
                @endforeach
            </nav>

            <div class="tt-side-rodape">
                <a class="tt-portal" href="{{ route('welcome') }}">
                    <img src="{{ asset('images/treetask/logo-sn.png') }}" alt="spigo.net">
                    <span>Portal<br><b>Spigo.net</b></span>
                </a>
                @if($usuario)
                    <div class="tt-user">
                        <span class="tt-av g" style="background: {{ \App\Modules\TreeTask\Support\Aparencia::corAvatar($usuario->id) }}">{{ \App\Modules\TreeTask\Support\Aparencia::iniciais($usuario->name) }}</span>
                        <a class="tt-user-nome" href="{{ route('profile') }}" title="Meu perfil" style="text-decoration:none">{{ $usuario->name }}</a>
                        <form method="POST" action="{{ route('treetask.sair') }}">
                            @csrf
                            <button type="submit" class="tt-icbtn" title="Sair" aria-label="Sair">
                                <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </aside>

        {{-- Barra superior (mobile) --}}
        <header class="tt-top">
            <a class="tt-brand" href="{{ route('treetask.index') }}" aria-label="TreeTask">
                <span class="tt-brand-icone"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $arvore !!}</svg></span>
                <span class="tt-brand-nome">TreeTask</span>
            </a>
            <div class="tt-top-dir">
                <button type="button" class="tt-icbtn" @click="mods = true" aria-label="Módulos do portal">
                    <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true">{!! $grade !!}</svg>
                </button>
                @if($usuario)
                    <a href="{{ route('profile') }}" class="tt-av g" style="background: {{ \App\Modules\TreeTask\Support\Aparencia::corAvatar($usuario->id) }};border:0;text-decoration:none" aria-label="Meu perfil">{{ \App\Modules\TreeTask\Support\Aparencia::iniciais($usuario->name) }}</a>
                @endif
            </div>
        </header>

        <main class="tt-main">
            @if(session('success'))
                <div class="tt-alert ok" role="status">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="tt-alert erro" role="alert">{{ session('error') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @if($fab)
        <a href="{{ $fab }}" class="tt-fab" aria-label="Criar novo">
            <svg class="tt-i l" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        </a>
    @endif

    <nav class="tt-tabs" aria-label="TreeTask">
        @foreach($itensMenu as $chave => $item)
            <a href="{{ route($item['rota']) }}" @class(['on' => $ativo === $chave])>
                <svg class="tt-i l" viewBox="0 0 24 24" aria-hidden="true">{!! $item['icone'] !!}</svg>{{ $item['curto'] }}
            </a>
        @endforeach
    </nav>

    {{-- Troca de módulos: painel flutuante no desktop, tela cheia no mobile --}}
    <div x-show="mods" x-cloak class="tt-modfundo" @click="mods = false"></div>
    <div x-show="mods" x-cloak class="tt-modpanel" role="dialog" aria-label="Módulos do portal">
        <div class="tt-modtit">
            <span>Módulos do portal</span>
            <button type="button" class="tt-icbtn" @click="mods = false" aria-label="Fechar">
                <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="tt-modgrid">
            @forelse($appsPortal ?? [] as $app)
                <a href="{{ url($app->start_link) }}" @class(['tt-modi', 'cur' => $app->id === 3])>
                    <img src="{{ $app->icon_url }}" alt="">
                    <span><b>{{ $app->title }}</b>@if($app->id === 3)<small>Você está aqui</small>@endif</span>
                </a>
            @empty
                <p class="tt-muted" style="grid-column:1/-1;padding:8px">Nenhum módulo disponível.</p>
            @endforelse
        </div>
        <a class="tt-modtodos" href="{{ route('welcome') }}">Ver todos os módulos</a>
    </div>
@endif
@livewireScripts
</body>
</html>
