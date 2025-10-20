<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Portal Spigo.Net</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />


    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans bg-spigo-dark text-white/80">
<div class="relative min-h-screen flex flex-col">
    <!-- Navigation -->
    <header class="w-full sticky top-0 bg-spigo-dark bg-opacity-90 backdrop-blur-sm z-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div>
                    <!-- IMPORTANTE: Substitua pelo caminho do seu logo pequeno -->
                    <img src="//spigo.net/manual/Spigo.Net_Marcadagua 2_Colorido.png" alt="Spigo.Net Logo Pequeno" class="h-10 w-auto"
                         onerror="this.onerror=null; this.src='https://placehold.co/150x50/322C3A/FFFFFF?text=Spigo.Net';">
                </div>
                <nav>
                    @if (Route::has('login'))
                        <div class="flex items-center gap-6">
                            @auth
                                <a
                                    href="{{ url('/dashboard') }}"
                                    class="rounded-md px-3 py-2 text-spigo-lime ring-1 ring-transparent transition hover:text-white/70 focus:outline-none focus-visible:ring-[#FF2D20]"
                                >
                                    Dashboard
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="rounded-md px-3 py-2 text-spigo-light-blue ring-1 ring-transparent transition hover:text-white/70 focus:outline-none focus-visible:ring-[#FF2D20]"
                                >
                                    Log in
                                </a>

                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="rounded-md px-3 py-2 text-spigo-light-blue ring-1 ring-transparent transition hover:text-white/70 focus:outline-none focus-visible:ring-[#FF2D20]"
                                    >
                                        Register
                                    </a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        <div class="max-w-7xl mx-auto py-12 px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl lg:text-5xl font-bold text-spigo-lime tracking-tight">
                    Aplicações de Acesso Público
                </h1>
                <p class="mt-4 text-lg text-spigo-violet max-w-2xl mx-auto">
                    Explore as ferramentas disponíveis para todos os visitantes. Para mais aplicações, faça login.
                </p>
            </div>

            <div class="space-y-12">
                @forelse ($packages as $package)
                    <div>
                        <!-- Cabeçalho do Pacote -->
                        <div class="mb-4">
                            <h3 class="text-2xl font-bold tracking-tight text-white">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="font-normal text-spigo-violet mt-1">{{ $package->description }}</p>
                            @endif
                        </div>
                        <!-- Corpo do Pacote com os ícones dos Apps -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                            @foreach ($package->portalApps as $app)
                                <a href="{{ url($app->start_link) }}" class="group block p-6 text-center bg-white/5 rounded-lg shadow-lg hover:bg-white/10 transition-all duration-300 transform hover:-translate-y-1">
                                    <i class="{{ $app->icon }} fa-3x mb-4 text-spigo-lime transition-transform group-hover:scale-110"></i>
                                    <h5 class="font-bold tracking-tight text-white text-md">
                                        {{ $app->title }}
                                    </h5>
                                    <p class="text-xs text-spigo-violet mt-1">{{ $app->description }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="bg-white/5 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-10 text-center text-spigo-violet">
                            <p>Nenhum aplicativo público disponível no momento.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-8 text-center text-sm text-white/50">
        Spigo.Net &copy; {{ date('Y') }}
    </footer>
</div>
</body>
</html>

