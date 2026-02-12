<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use App\Models\PortalApp;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

new class extends Component {
    public Collection $shortcutApps;

    // --- PROPRIEDADES DO MÓDULO ---
    public ?int $moduleId = null; // ID recebido do Layout
    public string $moduleName = '';
    public string $moduleHomeRoute = '';
    public string $moduleMenu = '';
    public string $moduleIcon = '';
    public string $header = '';

    public function mount()
    {
        $this->shortcutApps = collect();

        if (auth()->check()) {
            $user = auth()->user();

            // Carrega todos os apps que o usuário tem acesso (Públicos + Privados + Específicos)
            $this->shortcutApps = PortalApp::query()
                ->where('visibility', 'public')
                ->orWhere('visibility', 'private')
                ->orWhereHas('users', fn($q) => $q->where('user_id', $user->id))
                ->orderBy('title')
                ->get();

            // Lógica Inteligente: Se recebermos o ID, preenchemos os dados automaticamente

            if ($this->moduleId) {
                // Busca na coleção em memória (sem nova query no banco)
                $currentApp = $this->shortcutApps->firstWhere('id', $this->moduleId);

                if ($currentApp) {
                    $this->moduleName = $currentApp->title;
                    $this->moduleIcon = $currentApp->icon;
                    $this->moduleHomeRoute = $currentApp->start_link;
                }
            }
        }
    }

    public function logout(Logout $logout): void
    {
        $origin = Session::get('module_origin');

        $logout();

        if ($origin === 'mundos-de-mim') {
            $this->redirect(route('mundos-de-mim.landing'), navigate: true);
            return;
        }

        $this->redirect(route('welcome'), navigate: true);
    }

    // Helper simples para resolver o link (Rota Laravel ou URL Direta)
    public function resolveHomeLink()
    {
        if (empty($this->moduleHomeRoute))
            return '#';

        // Se começar com / ou http, é uma URL direta (padrão do banco)
        if (Str::startsWith($this->moduleHomeRoute, ['/', 'http'])) {
            return url($this->moduleHomeRoute);
        }

        // Caso contrário, tenta resolver como rota nomeada (legado)
        try {
            return route($this->moduleHomeRoute);
        } catch (\Exception $e) {
            return '#';
        }
    }
}; ?>

<nav x-data="{ open: false }"
    class="bg-spigo-dark border-b border-white/10 sticky top-0 z-50 backdrop-blur-md bg-opacity-90">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- LADO ESQUERDO: Logo + Contexto da Aplicação Atual --}}
            <div class="flex items-center gap-4">

                {{-- 1. Logo Principal --}}
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('welcome') }}" wire:navigate title="Portal Spigo"
                        class="transition hover:opacity-80">
                        <img src="//spigo.net/manual/Spigo.Net_Marcadagua 2_Colorido.png" alt="Spigo"
                            class="h-8 w-auto block"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                        <span class="hidden text-xl font-bold text-white tracking-tight">SPIGO<span
                                class="text-spigo-lime">.NET</span></span>
                    </a>
                </div>

                {{-- 2. Área do Módulo Específico --}}
                @if($moduleName)
                    <div class="hidden md:flex items-center h-8 border-l border-white/20 pl-4 space-x-4">

                        {{-- Nome/Link do Módulo --}}
                        <a href="{{ $this->resolveHomeLink() }}" class="flex items-center gap-2 group">
                            @if($moduleIcon)
                                <div class="w-8 h-8 flex items-center justify-center">
                                    {{-- Verifica se é HTML (ex: <i class..>) ou Caminho de Imagem --}}
                                        @if(str_contains($moduleIcon, '<'))
                                            {!! $moduleIcon !!}
                                        @else
                                            <img src="{{ asset($moduleIcon) }}" alt="{{ $moduleName }}"
                                                class="w-full h-full object-contain group-hover:scale-110 transition-transform">
                                        @endif
                                </div>
                            @else
                                <i class="fa-solid fa-layer-group text-lg"></i>
                            @endif

                            <span class="font-bold text-gray-200 group-hover:text-white tracking-wide text-sm uppercase">
                                {{ $moduleName }}
                            </span>
                        </a>

                        {{-- Menu Específico da Aplicação --}}
                        @if($moduleMenu)
                            <div class="flex items-center gap-1 bg-white/5 rounded-lg px-2 py-1">
                                {!! $moduleMenu !!}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- LADO DIREITO: App Switcher + Perfil --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">

                @auth
                    {{-- 3. APP SWITCHER (Grid com Imagens) --}}
                    <x-dropdown align="right" width="80">
                        <x-slot name="trigger">
                            <button
                                class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-full transition focus:outline-none focus:ring-2 focus:ring-spigo-lime/50"
                                title="Meus Aplicativos">
                                <i class="fa-solid fa-grip text-xl"></i>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="p-4 w-[320px]">
                                <div class="text-xs font-bold text-gray-500 uppercase mb-3 px-1">Navegação Rápida</div>

                                @if($shortcutApps->isNotEmpty())
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach($shortcutApps as $app)
                                            <a href="{{ url($app->start_link) }}"
                                                class="flex flex-col items-center justify-center p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition group text-center h-24">
                                                <div class="h-8 w-8 mb-2 flex items-center justify-center">
                                                    <img src="{{ asset($app->icon) }}" alt="{{ $app->title }}"
                                                        class="w-full h-full object-contain group-hover:scale-110 transition-transform"
                                                        onerror="this.src='{{ asset('images/default-app-icon.png') }}'; this.onerror=null;">
                                                </div>
                                                <span
                                                    class="text-xs font-medium text-gray-700 dark:text-gray-300 leading-tight line-clamp-2">
                                                    {{ $app->title }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 text-center">
                                        <a href="{{ route('welcome') }}"
                                            class="text-xs text-spigo-blue hover:text-spigo-lime transition">
                                            Ver todos os aplicativos
                                        </a>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 text-center py-4">Nenhum app disponível.</p>
                                @endif
                            </div>
                        </x-slot>
                    </x-dropdown>

                    {{-- 4. Dropdown de Perfil --}}
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-300 bg-transparent hover:text-white focus:outline-none transition ease-in-out duration-150 gap-2">
                                <div class="text-right hidden lg:block">
                                    <div class="text-xs text-gray-400">Logado como</div>
                                    <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                                        x-on:profile-updated.window="name = $event.detail.name"
                                        class="font-bold text-white"></div>
                                </div>
                                @if(auth()->user()->avatar)
                                    <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}"
                                        class="h-8 w-8 rounded-full border border-spigo-lime/50 object-cover">
                                @else
                                    <div
                                        class="h-8 w-8 rounded-full bg-spigo-lime/20 flex items-center justify-center text-spigo-lime border border-spigo-lime/50 group-hover:bg-spigo-lime group-hover:text-spigo-dark transition">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Gerenciar Conta') }}
                            </div>

                            <x-dropdown-link :href="route('profile')" wire:navigate>
                                <i class="fa-regular fa-user mr-2"></i> {{ __('Meu Perfil') }}
                            </x-dropdown-link>

                            <div class="border-t border-gray-200 dark:border-gray-600"></div>

                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>
                                    <i class="fa-solid fa-arrow-right-from-bracket mr-2 text-red-400"></i> {{ __('Sair') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-300 hover:text-white transition"
                        wire:navigate>Entrar</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-sm text-gray-300 hover:text-white transition"
                            wire:navigate>Cadastrar</a>
                    @endif
                @endauth
            </div>

            {{-- Botão Mobile --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menu Mobile --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-spigo-dark border-t border-gray-700">
        @if($moduleName)
            <div class="bg-white/5 p-4 border-b border-gray-700">
                <div class="text-spigo-lime font-bold uppercase text-xs mb-2">Você está em:</div>
                <div class="text-white font-bold text-lg mb-2 flex items-center gap-2">
                    @if($moduleIcon)
                        @if(str_contains($moduleIcon, '<'))
                            {!! $moduleIcon !!}
                        @else
                            <img src="{{ asset($moduleIcon) }}" alt="{{ $moduleName }}" class="w-6 h-6 object-contain">
                        @endif
                    @endif
                    {{ $moduleName }}
                </div>
                @if($moduleMenu)
                    <div class="flex flex-wrap gap-2">
                        {!! $moduleMenu !!}
                    </div>
                @endif
            </div>
        @endif

        @auth
            <div class="pt-4 pb-1 border-t border-gray-700">
                <div class="px-4 flex items-center gap-3">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}"
                            class="h-10 w-10 rounded-full border border-spigo-lime/50 object-cover">
                    @else
                        <div
                            class="h-10 w-10 rounded-full bg-spigo-lime/20 flex items-center justify-center text-spigo-lime border border-spigo-lime/50">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <div class="font-medium text-base text-gray-200">{{ auth()->user()->name }}</div>
                        <div class="font-medium text-sm text-gray-400">{{ auth()->user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('welcome')" :active="request()->routeIs('welcome')">
                        <i class="fa-solid fa-house mr-2"></i> {{ __('Dashboard / Apps') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('profile')" wire:navigate>
                        <i class="fa-regular fa-user mr-2"></i> {{ __('Meu Perfil') }}
                    </x-responsive-nav-link>

                    <button wire:click="logout" class="w-full text-start">
                        <x-responsive-nav-link>
                            <i class="fa-solid fa-arrow-right-from-bracket mr-2 text-red-400"></i> {{ __('Sair') }}
                        </x-responsive-nav-link>
                    </button>
                </div>
            </div>

            {{-- Mobile App Switcher --}}
            @if($shortcutApps->isNotEmpty())
                <div class="pt-4 pb-4 border-t border-gray-700">
                    <div class="px-4 text-xs font-bold text-gray-500 uppercase mb-3">Meus Aplicativos</div>
                    <div class="grid grid-cols-3 gap-2 px-2">
                        @foreach($shortcutApps as $app)
                            <a href="{{ url($app->start_link) }}"
                                class="flex flex-col items-center justify-center p-2 rounded-xl hover:bg-white/5 transition text-center h-20">
                                <div class="h-6 w-6 mb-1 flex items-center justify-center">
                                    <img src="{{ asset($app->icon) }}" alt="{{ $app->title }}" class="w-full h-full object-contain"
                                        onerror="this.src='{{ asset('images/default-app-icon.png') }}'; this.onerror=null;">
                                </div>
                                <span class="text-[10px] font-medium text-gray-400 leading-tight line-clamp-2">
                                    {{ $app->title }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="pt-4 pb-1 border-t border-gray-700">
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')" wire:navigate>
                        <i class="fa-solid fa-right-to-bracket mr-2"></i> {{ __('Entrar') }}
                    </x-responsive-nav-link>
                    @if (Route::has('register'))
                        <x-responsive-nav-link :href="route('register')" wire:navigate>
                            <i class="fa-solid fa-user-plus mr-2"></i> {{ __('Cadastrar') }}
                        </x-responsive-nav-link>
                    @endif
                </div>
            </div>
        @endauth
    </div>
</nav>