<x-app-layout>
    {{-- Removemos o header padrão para criar um customizado abaixo --}}

    <div class="py-10 min-h-screen bg-spigo-dark relative overflow-hidden">

        {{-- Elementos de fundo decorativos (Glow) --}}
        <div class="absolute top-0 left-0 w-full h-96 bg-spigo-violet/10 blur-3xl -z-10 rounded-full pointer-events-none transform -translate-y-1/2"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- 1. Cabeçalho e Saudação --}}
            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white tracking-tight">
                        Olá, <span class="text-spigo-lime">{{ Auth::user()->name }}</span>
                    </h1>
                    <p class="text-gray-400 mt-2 text-lg">
                        Bem-vindo ao seu Portal de Operações.
                    </p>
                </div>

                {{-- 2. Barra de Busca (Vanilla JS) --}}
                <div class="w-full md:w-1/3 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-500"></i>
                    </div>
                    <input type="text"
                           id="appSearch"
                           class="block w-full pl-10 pr-3 py-3 border border-gray-700 rounded-xl leading-5 bg-white/5 text-gray-300 placeholder-gray-500 focus:outline-none focus:bg-white/10 focus:border-spigo-lime focus:ring-1 focus:ring-spigo-lime sm:text-sm transition duration-150 ease-in-out"
                           placeholder="Buscar aplicativo ou ferramenta..."
                           autocomplete="off">
                </div>
            </div>

            {{-- Feedback de Mensagens (Erro/Sucesso) --}}
            @if (session('error'))
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-lg mb-8 flex items-center gap-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif
            @if (session('success'))
                <div class="bg-green-500/10 border border-green-500/50 text-green-400 p-4 rounded-lg mb-8 flex items-center gap-3" role="alert">
                    <i class="fa-solid fa-check-circle"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            {{-- 3. Grid de Aplicativos --}}
            <div class="space-y-12" id="packagesContainer">
                @forelse ($packages as $package)
                    <div class="package-section" data-package-name="{{ strtolower($package->name) }}">
                        {{-- Título do Pacote --}}
                        <div class="flex items-center gap-3 mb-5 border-b border-gray-700 pb-2">
                            <div class="w-1 h-6 bg-spigo-lime rounded-full"></div>
                            <h3 class="text-xl font-bold text-white tracking-wide uppercase text-opacity-90">
                                {{ $package->name }}
                            </h3>
                        </div>

                        {{-- Grid de Cards --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            @foreach ($package->visible_apps as $app)
                                <a href="{{ url($app->start_link) }}"
                                   class="app-card group relative flex flex-col p-6 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 hover:border-spigo-lime/50 hover:shadow-lg hover:shadow-spigo-lime/10 transition-all duration-300 ease-out transform hover:-translate-y-1"
                                   data-app-name="{{ strtolower($app->title) }}"
                                   data-app-desc="{{ strtolower($app->description) }}">

                                    {{-- Ícone IMAGEM --}}
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="p-3 bg-gray-800 rounded-lg group-hover:bg-spigo-lime group-hover:bg-opacity-20 transition-colors duration-300">
                                            <img src="{{ asset($app->icon) }}"
                                                 alt="{{ $app->title }}"
                                                 class="w-8 h-8 object-contain transition-transform duration-300 group-hover:scale-110"
                                                 onerror="this.src='{{ asset('images/default-app-icon.png') }}'; this.onerror=null;">
                                        </div>
                                        <i class="fa-solid fa-arrow-right text-gray-600 group-hover:text-white transition-colors text-sm opacity-0 group-hover:opacity-100 transform translate-x-[-10px] group-hover:translate-x-0 duration-300"></i>
                                    </div>

                                    {{-- Textos --}}
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-100 group-hover:text-white mb-1">
                                            {{ $app->title }}
                                        </h4>
                                        <p class="text-sm text-gray-400 line-clamp-2 group-hover:text-gray-300">
                                            {{ $app->description }}
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 bg-white/5 rounded-3xl border border-dashed border-gray-700">
                        <i class="fa-regular fa-folder-open text-4xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400 text-lg">Nenhum aplicativo atribuído à sua conta.</p>
                        <p class="text-gray-600 text-sm mt-2">Entre em contato com o administrador.</p>
                    </div>
                @endforelse

                {{-- Mensagem de "Nenhum resultado" para a busca --}}
                <div id="noResults" class="hidden text-center py-12">
                    <p class="text-gray-500 text-lg">Nenhum aplicativo encontrado para sua busca.</p>
                </div>
            </div>

        </div>
    </div>

    {{-- 4. Script Vanilla JS para Filtro --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('appSearch');
            const appCards = document.querySelectorAll('.app-card');
            const packageSections = document.querySelectorAll('.package-section');
            const noResults = document.getElementById('noResults');

            if(searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase();
                    let hasVisibleApps = false;

                    // Filtra cards individuais
                    appCards.forEach(card => {
                        const title = card.getAttribute('data-app-name');
                        const desc = card.getAttribute('data-app-desc');

                        if (title.includes(term) || desc.includes(term)) {
                            card.style.display = 'flex'; // Restaura display flex
                            hasVisibleApps = true;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Esconde seções vazias
                    packageSections.forEach(section => {
                        const totalCards = section.querySelectorAll('.app-card');
                        const hiddenCards = section.querySelectorAll('.app-card[style="display: none;"]');

                        if (hiddenCards.length === totalCards.length) {
                            section.style.display = 'none';
                        } else {
                            section.style.display = 'block';
                        }
                    });

                    // Mensagem de Sem Resultados
                    if (!hasVisibleApps && term.length > 0) {
                        noResults.classList.remove('hidden');
                    } else {
                        noResults.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</x-app-layout>
