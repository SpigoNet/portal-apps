@php
    $papel = request()->routeIs('mundos-de-mim.admin.themes.*', 'mundos-de-mim.admin.prompts.*', 'mundos-de-mim.admin.importador.*');
    $emAdmin = request()->routeIs('mundos-de-mim.admin.*', 'mundos-de-mim.playground.*');
    $usuario = auth()->user();
    $atual = fn (string ...$rotas) => request()->routeIs(...$rotas) ? 'page' : 'false';
    $linksPrincipais = [
        ['Início', 'mundos-de-mim.index', ['mundos-de-mim.index']],
        ['Mundos', 'mundos-de-mim.estilos.index', ['mundos-de-mim.estilos.*']],
        ['Postais', 'mundos-de-mim.galeria.index', ['mundos-de-mim.galeria.*']],
        ['Retrato', 'mundos-de-mim.perfil.index', ['mundos-de-mim.perfil.*']],
        ['Companhias', 'mundos-de-mim.pessoas.index', ['mundos-de-mim.pessoas.*']],
    ];
    $linksAdmin = [
        ['Ateliê', 'mundos-de-mim.playground.index'],
        ['Mundos e prompts', 'mundos-de-mim.admin.themes.index'],
        ['Importador', 'mundos-de-mim.admin.importador.index'],
        ['Vitrine pública', 'mundos-de-mim.admin.gallery.index'],
        ['Atlas dos usuários', 'mundos-de-mim.admin.user-gallery.index'],
        ['Parâmetros de imagem', 'mundos-de-mim.admin.parametros-imagem.index'],
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#14122B">

    <title>Mundos de Mim</title>
    <x-pwa-head start-link="/mundos-de-mim" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('MundosDeMim::partials.tema')
</head>
<body class="mm antialiased min-h-screen flex flex-col">

    <header x-data="{ aberto: false }" style="border-bottom:1px solid #2c2860;position:sticky;top:0;z-index:40;background:var(--mm-noite)">
        <div style="max-width:1280px;margin:0 auto;padding:12px 24px;display:flex;flex-wrap:wrap;gap:12px 28px;align-items:center">
            <a href="{{ route('mundos-de-mim.index') }}" aria-label="Mundos de Mim, ir para o início">
                <x-MundosDeMim::logo />
            </a>

            <button type="button" @click="aberto = !aberto" :aria-expanded="aberto" aria-label="Abrir menu"
                    class="mm-hamburguer" style="margin-left:auto;padding:12px;background:none;border:0;color:var(--mm-lua);cursor:pointer">
                <i class="fa-solid fa-bars" style="font-size:20px"></i>
            </button>

            <div class="mm-barra" :class="{ 'mm-barra-aberta': aberto }">
                <nav aria-label="Principal" style="display:flex;flex-wrap:wrap;gap:4px;flex-grow:1;align-items:center">
                    @foreach($linksPrincipais as [$rotulo, $rota, $padroes])
                        <a href="{{ route($rota) }}" class="mm-nav-link" aria-current="{{ $atual(...$padroes) }}">{{ $rotulo }}</a>
                    @endforeach

                    @can('admin-do-app')
                        <div x-data="{ menu: false }" style="position:relative" @click.outside="menu = false" @keydown.escape="menu = false">
                            <button type="button" class="mm-nav-link" @click="menu = !menu" :aria-expanded="menu" aria-haspopup="true"
                                    aria-current="{{ $emAdmin ? 'true' : 'false' }}" style="{{ $emAdmin ? 'color:var(--mm-poente)' : '' }}">
                                Admin <i class="fa-solid fa-chevron-down" style="font-size:10px"></i>
                            </button>
                            <div class="mm-menu" x-show="menu" x-cloak>
                                @foreach($linksAdmin as [$rotulo, $rota])
                                    <a href="{{ route($rota) }}">{{ $rotulo }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endcan
                </nav>

                <div x-data="{ menu: false }" style="position:relative" @click.outside="menu = false" @keydown.escape="menu = false">
                    <button type="button" @click="menu = !menu" :aria-expanded="menu" aria-haspopup="true"
                            style="display:flex;align-items:center;gap:12px;background:none;border:0;color:inherit;cursor:pointer;padding:4px;font-family:inherit">
                        <span style="text-align:right;line-height:1.2">
                            <span style="display:block;font-size:12px;color:var(--mm-texto-suave)">Atlas de</span>
                            <span style="display:block;font-size:15px;font-weight:600">{{ \Illuminate\Support\Str::before($usuario->name ?? 'Viajante', ' ') }}</span>
                        </span>
                        @if($usuario->avatar ?? null)
                            <img src="{{ $usuario->avatar }}" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                        @else
                            <span aria-hidden="true" style="width:40px;height:40px;border-radius:50%;background:var(--mm-bruma);color:var(--mm-noite);display:flex;align-items:center;justify-content:center;font-weight:700">{{ mb_strtoupper(mb_substr($usuario->name ?? 'V', 0, 1)) }}</span>
                        @endif
                    </button>
                    <div class="mm-menu" x-show="menu" x-cloak style="left:auto;right:0">
                        <form method="POST" action="{{ route('mundos-de-mim.logout') }}">
                            @csrf
                            <button type="submit">Sair do atlas</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    @if(isset($header))
        <div class="{{ $papel ? 'mm-papel-cabecalho' : '' }}" style="{{ $papel ? '' : 'border-bottom:1px solid #2c2860' }}">
            <div style="max-width:1280px;margin:0 auto;padding:20px 24px">
                <div class="mm-display" style="font-size:22px;font-weight:700;{{ $papel ? 'color:var(--mm-noite)' : '' }}">
                    {{ $header }}
                </div>
            </div>
        </div>
    @endif

    <main class="flex-grow {{ $papel ? 'mm-papel' : 'mm-pagina' }}">
        {{ $slot }}
    </main>

    <footer style="border-top:1px solid #2c2860;margin-top:auto">
        <div style="max-width:1280px;margin:0 auto;padding:24px;display:flex;flex-wrap:wrap;gap:8px 24px;justify-content:space-between;font-size:14px;color:var(--mm-texto-suave)">
            <span>© {{ date('Y') }} Mundos de Mim · um app Spigo.net</span>
            <span>Um postal novo, todo dia.</span>
        </div>
    </footer>

    <div id="mm-aguarde" class="hidden" role="alertdialog" aria-live="assertive" aria-labelledby="mm-aguarde-titulo"
         style="position:fixed;inset:0;z-index:100;background:rgba(20,18,43,.92);display:flex;align-items:center;justify-content:center;padding:24px">
        <div style="max-width:380px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:20px">
            <span aria-hidden="true" class="mm-giro" style="width:56px;height:56px;border-radius:50%;border:5px solid var(--mm-linha);border-top-color:var(--mm-poente)"></span>
            <h2 id="mm-aguarde-titulo" class="mm-display" style="margin:0;font-size:28px;font-weight:700;color:var(--mm-lua)">Aguarde, processando…</h2>
            <p style="margin:0;font-size:16px;line-height:1.5;color:var(--mm-texto-suave)">Pode levar alguns instantes. Não feche nem recarregue esta página.</p>
        </div>
    </div>
    <script>
        (function () {
            const painel = document.getElementById('mm-aguarde');
            const titulo = document.getElementById('mm-aguarde-titulo');
            window.mmAguarde = function (texto) {
                titulo.textContent = texto || 'Aguarde, processando…';
                painel.classList.remove('hidden');
            };
            window.mmLiberar = function () { painel.classList.add('hidden'); };

            document.addEventListener('submit', function (e) {
                const form = e.target;
                const texto = form.dataset ? form.dataset.aguarde : undefined;
                if (texto === undefined || !form.checkValidity()) return;
                // Se outro handler cancelou o envio (ex.: confirm), não mostra o aviso
                setTimeout(function () {
                    if (e.defaultPrevented) return;
                    window.mmAguarde(texto);
                    form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; });
                }, 0);
            });

            // Voltar pelo navegador não deve manter a tela de espera
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    window.mmLiberar();
                    document.querySelectorAll('form[data-aguarde] button[type="submit"]').forEach(function (b) { b.disabled = false; });
                }
            });
        })();
    </script>

    @livewireScripts
</body>
</html>
