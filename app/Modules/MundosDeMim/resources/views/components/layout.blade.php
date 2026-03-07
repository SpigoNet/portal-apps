<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Mundos de Mim</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Scripts -->
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; background-color: #F8F9FA; color: #1E1E24; }
        .font-heading { font-family: 'Space Grotesk', sans-serif; }
        .bg-dark-tech {
            background-color: #1E1E24;
            background-image: radial-gradient(circle at top right, rgba(123, 44, 191, 0.15), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(0, 119, 182, 0.15), transparent 40%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col">

    <!-- Navbar Exclusiva do Módulo -->
    <nav x-data="{ open: false, profileOpen: false }" class="bg-dark-tech text-white sticky top-0 z-50 shadow-lg">
        <div class="glass px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Esquerda: Logo e Links -->
                <div class="flex items-center gap-8">
                    <a href="{{ route('mundos-de-mim.index') }}" class="flex items-center gap-2 hover:opacity-80 transition">
                        <span class="text-xl font-black tracking-tighter font-heading">MUNDOS DE <span class="text-[#62A87C]">MIM</span></span>
                    </a>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('mundos-de-mim.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('mundos-de-mim.index') ? 'bg-[#62A87C]/20 text-[#62A87C]' : 'text-slate-300 hover:text-white hover:bg-white/5' }} transition">Dashboard</a>
                        <a href="{{ route('mundos-de-mim.perfil.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('mundos-de-mim.perfil.*') ? 'bg-[#3B9AB2]/20 text-[#3B9AB2]' : 'text-slate-300 hover:text-white hover:bg-white/5' }} transition">Meu Perfil</a>
                        <a href="{{ route('mundos-de-mim.pessoas.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('mundos-de-mim.pessoas.*') ? 'bg-[#7B2CBF]/20 text-[#7B2CBF]' : 'text-slate-300 hover:text-white hover:bg-white/5' }} transition">Entes Queridos</a>
                        <a href="{{ route('mundos-de-mim.galeria.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('mundos-de-mim.galeria.*') ? 'bg-[#0077B6]/20 text-[#0077B6]' : 'text-slate-300 hover:text-white hover:bg-white/5' }} transition">Minha Galeria</a>
                        <a href="{{ route('mundos-de-mim.estilos.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('mundos-de-mim.estilos.*') ? 'bg-[#62A87C]/20 text-[#62A87C]' : 'text-slate-300 hover:text-white hover:bg-white/5' }} transition">Estilos & Temas</a>
                        
                        @can('admin-do-app')
                        <div x-data="{ adminOpen: false }" class="relative ml-2">
                            <button @click="adminOpen = !adminOpen" @click.away="adminOpen = false" class="px-3 py-2 rounded-md text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 transition flex items-center gap-1">
                                Admin <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </button>
                            <div x-show="adminOpen" x-cloak class="absolute left-0 mt-2 w-48 bg-[#141419] border border-white/10 rounded-xl shadow-xl overflow-hidden z-50">
                                <a href="{{ route('mundos-de-mim.playground.index') }}" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/5 transition">Playground</a>
                                <a href="{{ route('mundos-de-mim.admin.themes.index') }}" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/5 transition">Temas</a>
                                <a href="{{ route('mundos-de-mim.admin.importador.index') }}" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/5 transition">Importador</a>
                                <a href="{{ route('mundos-de-mim.admin.gallery.index') }}" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/5 transition">Galeria Pública</a>
                                <a href="{{ route('mundos-de-mim.admin.user-gallery.index') }}" class="block px-4 py-2 text-sm text-slate-300 hover:bg-white/5 transition">Galeria de Usuários</a>
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>

                <!-- Direita: Perfil -->
                <div class="hidden md:flex items-center">
                    <div class="relative" x-data="{ profileOpen: false }">
                        <button @click="profileOpen = !profileOpen" @click.away="profileOpen = false" class="flex items-center gap-3 focus:outline-none pl-4 border-l border-white/20">
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Estúdio de</div>
                                <div class="text-sm font-bold text-white">{{ auth()->user()->name ?? 'Usuário' }}</div>
                            </div>
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-9 h-9 rounded-full border border-[#62A87C]/50 object-cover">
                            @else
                                <div class="w-9 h-9 rounded-full bg-[#62A87C]/20 border border-[#62A87C]/50 flex items-center justify-center text-[#62A87C] font-bold">
                                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                                </div>
                            @endif
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                        </button>

                        <div x-show="profileOpen" x-cloak class="absolute right-0 mt-2 w-48 bg-[#141419] border border-white/10 rounded-xl shadow-xl overflow-hidden z-50">
                            <form method="POST" action="{{ route('mundos-de-mim.logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-white/5 transition flex items-center gap-2">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Sair do Estúdio
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Botão Mobile -->
                <div class="flex items-center md:hidden">
                    <button @click="open = !open" class="text-slate-300 hover:text-white focus:outline-none p-2">
                        <i class="fa-solid fa-bars text-xl" x-show="!open"></i>
                        <i class="fa-solid fa-xmark text-xl" x-show="open" x-cloak></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Mobile -->
        <div x-show="open" x-cloak class="md:hidden bg-[#141419] border-t border-white/10">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="{{ route('mundos-de-mim.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('mundos-de-mim.index') ? 'bg-[#62A87C]/20 text-[#62A87C]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Dashboard</a>
                <a href="{{ route('mundos-de-mim.perfil.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('mundos-de-mim.perfil.*') ? 'bg-[#3B9AB2]/20 text-[#3B9AB2]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Meu Perfil</a>
                <a href="{{ route('mundos-de-mim.pessoas.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('mundos-de-mim.pessoas.*') ? 'bg-[#7B2CBF]/20 text-[#7B2CBF]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Entes Queridos</a>
                <a href="{{ route('mundos-de-mim.galeria.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('mundos-de-mim.galeria.*') ? 'bg-[#0077B6]/20 text-[#0077B6]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Minha Galeria</a>
                <a href="{{ route('mundos-de-mim.estilos.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('mundos-de-mim.estilos.*') ? 'bg-[#62A87C]/20 text-[#62A87C]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Estilos & Temas</a>
                
                @can('admin-do-app')
                    <div class="border-t border-white/10 my-2"></div>
                    <div class="px-3 py-1 text-xs uppercase font-bold text-slate-500 tracking-wider">Admin</div>
                    <a href="{{ route('mundos-de-mim.playground.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-300 hover:bg-white/5 hover:text-white">Playground</a>
                    <a href="{{ route('mundos-de-mim.admin.themes.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-300 hover:bg-white/5 hover:text-white">Temas</a>
                    <a href="{{ route('mundos-de-mim.admin.importador.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-300 hover:bg-white/5 hover:text-white">Importador</a>
                @endcan

                <div class="border-t border-white/10 my-2 pt-2">
                    <form method="POST" action="{{ route('mundos-de-mim.logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-red-400 hover:bg-white/5 hover:text-red-300">
                            Sair do Estúdio
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Opcional (Se houver $header injetado) -->
    @if(isset($header))
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endif

    <!-- Conteúdo Principal -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer do Módulo -->
    <footer class="bg-white border-t border-gray-200 mt-auto py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} Mundos de Mim. Todos os direitos reservados.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
