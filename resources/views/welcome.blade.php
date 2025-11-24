<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Spigo.Net</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans bg-spigo-dark text-white/80">
<div class="relative min-h-screen flex flex-col">

    <livewire:layout.navigation />

    <main class="flex-grow">
        <div class="max-w-7xl mx-auto py-12 px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl lg:text-5xl font-bold text-spigo-lime tracking-tight">
                    @auth
                        Meus Aplicativos
                    @else
                        Aplicações de Acesso Público
                    @endauth
                </h1>
                <p class="mt-4 text-lg text-spigo-violet max-w-2xl mx-auto">
                    @auth
                        Acesse suas ferramentas e aplicativos disponíveis.
                    @else
                        Explore as ferramentas disponíveis para todos os visitantes. Para mais aplicações, faça login.
                    @endauth
                </p>
            </div>

            <div class="space-y-12">
                @forelse ($packages as $package)
                    <div>
                        <div class="mb-4">
                            <h3 class="text-2xl font-bold tracking-tight text-white">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="font-normal text-spigo-violet mt-1">{{ $package->description }}</p>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                            {{-- Nota: Usamos visible_apps que foi populado no Controller --}}
                            @foreach ($package->visible_apps as $app)
                                <a href="{{ url($app->start_link) }}" class="group block p-6 text-center bg-white/5 rounded-lg shadow-lg hover:bg-white/10 transition-all duration-300 transform hover:-translate-y-1">
                                    <span class="fa-3x mb-4 text-spigo-lime transition-transform group-hover:scale-110">
                                        {{ $app->icon ?? '⚠️' }}
                                    </span>
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
                            <p>Nenhum aplicativo disponível no momento.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    <footer class="py-8 text-center text-sm text-white/50">
        Spigo.Net &copy; {{ date('Y') }}
    </footer>
</div>
</body>
</html>
