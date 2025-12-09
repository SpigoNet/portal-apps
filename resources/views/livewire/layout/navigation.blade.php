<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use App\Models\PortalApp;
use Illuminate\Support\Collection;

new class extends Component {
    public Collection $shortcutApps;

    // --- NOVAS PROPRIEDADES ---
    public string $moduleName = '';
    public string $moduleHomeRoute = '';

    // Defina explicitamente como string e inicie vazia
    public string $moduleMenu = '';
    // --------------------------
    public string $moduleIcon = '';
    public string $header = '';


    public function mount()
    {
        $this->shortcutApps = collect();

        if (auth()->check()) {
            $user = auth()->user();
            $this->shortcutApps = PortalApp::where('visibility', 'public')
                ->orWhere('visibility', 'private')
                ->orWhereHas('users', fn($q) => $q->where('user_id', $user->id))
                ->orderBy('title')
                ->get();
        }
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>
<nav x-data="{ open: false }" class="bg-spigo-dark/80 backdrop-blur-sm border-b border-white/10 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <div class="flex items-center">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('welcome') }}" wire:navigate title="Portal Spigo">
                        <img src="//spigo.net/manual/Spigo.Net_Marcadagua 2_Colorido.png" alt="Spigo.Net Logo"
                             class="block h-9 w-auto hover:opacity-80 transition">
                    </a>
                </div>

                @if($moduleName)
                    <div class="hidden sm:flex items-center ml-4 pl-4 border-l border-white/20 h-8 space-x-3">

                        <a href="{{ route($moduleHomeRoute) }}"
                           class="flex items-center gap-2 text-gray-300 hover:text-white transition group"
                           title="Início do Módulo {{ $moduleName }}">
                            <div class="p-1.5 rounded-md bg-white/5 group-hover:bg-spigo-lime/20 transition">
                                @if($moduleIcon)
                                    <div style="font-size: 24px">
                                        {!! $moduleIcon !!}
                                    </div>
                                @else
                                    <svg class="w-4 h-4 text-spigo-lime" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                @endif
                            </div>
                            <span class="font-bold tracking-wide text-sm">{{ $moduleName }}</span>
                        </a>

                        @if($moduleMenu)
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button
                                        class="flex items-center text-sm font-medium text-gray-400 hover:text-white bg-transparent focus:outline-none transition duration-150 ease-in-out px-2 py-1 rounded hover:bg-white/5">
                                        <div>Menu</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                 viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="py-1">
                                        {!! $moduleMenu !!}
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        @endif

                        @if($header)
                            <span class="font-bold tracking-wide text-sl text-white">
                                {!! $header !!}
                            </span>
                        @endif
                    </div>

                @endif
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-300 bg-transparent hover:text-white focus:outline-none transition ease-in-out duration-150">
                                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                                     x-on:profile-updated.window="name = $event.detail.name"></div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                         viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="block px-4 py-2 text-xs text-gray-400">{{ __('Minha Conta') }}</div>
                            <x-dropdown-link :href="route('profile')"
                                             wire:navigate>{{ __('Profile') }}</x-dropdown-link>

                            @if($shortcutApps->isNotEmpty())
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <div class="block px-4 py-2 text-xs text-gray-400">{{ __('Meus Aplicativos') }}</div>
                                <div class="max-h-64 overflow-y-auto">
                                    @foreach($shortcutApps as $app)
                                        <x-dropdown-link :href="url($app->start_link)">
                                            <div class="flex items-center">
                                                <span
                                                    class="{{ $app->icon }} w-4 mr-2 text-center text-spigo-lime">{{ $app->icon ?? '⚠️' }}</span>
                                                <span class="truncate">{{ $app->title }}</span>
                                            </div>
                                        </x-dropdown-link>
                                    @endforeach
                                </div>
                            @endif

                            <div class="border-t border-gray-200 dark:border-gray-600"></div>
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>{{ __('Log Out') }}</x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="space-x-4">
                        <a href="{{ route('login') }}" class="text-sm text-gray-300 hover:text-white transition"
                           wire:navigate>Log in</a>
                    </div>
                @endauth
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                              stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-spigo-dark border-t border-gray-700">

        @if($moduleName && $moduleMenu)
            <div class="pt-2 pb-2 space-y-1 bg-white/5 border-b border-gray-600">
                <div
                    class="px-4 py-2 text-xs text-spigo-lime uppercase font-bold tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h7"></path>
                    </svg>
                    {{ $moduleName }}
                </div>
                <div class="px-2">
                    {!! $moduleMenu !!}
                </div>
            </div>
        @endif

        @auth
            <div class="pt-4 pb-1">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-200">{{ auth()->user()->name }}</div>
                    <div class="font-medium text-sm text-gray-400">{{ auth()->user()->email }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile')"
                                           wire:navigate>{{ __('Profile') }}</x-responsive-nav-link>
                    <button wire:click="logout" class="w-full text-start">
                        <x-responsive-nav-link>{{ __('Log Out') }}</x-responsive-nav-link>
                    </button>
                </div>
            </div>
        @endauth
    </div>
</nav>
