<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Escolha seus Universos') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-10 text-center max-w-2xl mx-auto">
                <h1 class="text-3xl font-extrabold text-indigo-900 sm:text-4xl">Onde você quer viver hoje?</h1>
                <p class="mt-4 text-lg text-gray-500">
                    Ative os estilos que mais combinam com sua personalidade.
                    Nosso sistema irá gerar artes diárias apenas dos temas selecionados.
                </p>
            </div>

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                     class="fixed bottom-4 right-4 bg-indigo-600 text-white px-6 py-3 rounded-lg shadow-xl z-50 transition-all duration-500">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @foreach($themes as $theme)
                    @php
                        $isEnabled = in_array($theme->id, $userEnabledThemes);

                        // Prepara array de URLs para o JavaScript (Alpine.js)
                        // Isso cria uma lista: ['url1.jpg', 'url2.jpg', 'url3.jpg']
                        $exampleUrls = $theme->examples->map(function($ex) {
                            return Storage::url($ex->image_path);
                        })->values()->toArray();

                        // Fallback se não tiver nenhuma imagem
                        if (empty($exampleUrls)) {
                            $exampleUrls[] = 'https://via.placeholder.com/600x800?text=Sem+Exemplo';
                        }
                    @endphp

                    <div x-data="{
                images: {{ json_encode($exampleUrls) }},
                active: 0,
                timer: null,
                start() {
                    if (this.images.length > 1) {
                        this.timer = setInterval(() => {
                            this.active = (this.active + 1) % this.images.length;
                        }, 1000); // Muda a cada 1 segundo (ajuste se quiser mais rápido)
                    }
                },
                stop() {
                    clearInterval(this.timer);
                    this.active = 0; // Opcional: Volta para a primeira foto ao sair
                }
             }"
                         @mouseenter="start()"
                         @mouseleave="stop()"
                         class="relative group bg-white rounded-3xl shadow-lg overflow-hidden border transition-all duration-300 hover:shadow-2xl {{ $isEnabled ? 'border-indigo-500 ring-4 ring-indigo-100' : 'border-gray-200' }}">

                        @if($theme->is_seasonal)
                            <div class="absolute top-4 right-0 bg-gradient-to-l from-yellow-400 to-orange-400 text-white text-xs font-bold px-4 py-1 rounded-l-full z-20 shadow-lg transform group-hover:scale-110 transition-transform">
                                ✨ Edição Limitada
                            </div>
                        @endif

                        <div class="relative h-[500px] w-full bg-gray-100 overflow-hidden">

                            <img :src="images[active]"
                                 alt="Resultado {{ $theme->name }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">

                            <div x-show="images.length > 1"
                                 class="absolute top-4 left-4 bg-black/50 backdrop-blur-md text-white text-xs px-2 py-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                 x-text="(active + 1) + '/' + images.length">
                            </div>

                            @if($theme->example_input_path)
                                <div class="absolute bottom-6 left-6 z-10">
                                    <div class="w-32 h-32 rounded-2xl border-4 border-white shadow-2xl overflow-hidden bg-white transform transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-2 origin-bottom-left">
                                        <img src="{{ Storage::url($theme->example_input_path) }}" alt="Input" class="w-full h-full object-cover">

                                        <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-white text-[10px] text-center py-1 font-bold uppercase tracking-wider">
                                            Sua Referência
                                        </div>
                                    </div>

                                    <div class="absolute -top-8 left-0 bg-white text-gray-800 text-xs px-2 py-1 rounded shadow opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        Requer: {{ $theme->example_input_description ?? 'Foto Padrão' }}
                                    </div>
                                </div>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-80 group-hover:opacity-90 transition-opacity"></div>

                            <div class="absolute bottom-6 right-6 text-right max-w-[50%] pointer-events-none">
                                <h3 class="text-3xl font-black text-white leading-tight shadow-black drop-shadow-lg mb-1">{{ $theme->name }}</h3>
                                <p class="text-sm text-gray-300 font-medium">
                                    {{ $theme->is_seasonal ? 'Evento Especial' : 'Coleção Permanente' }}
                                </p>
                            </div>
                        </div>

                        <div class="p-6 flex items-center justify-between bg-white border-t border-gray-100">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Assinatura</span>
                                @if($isEnabled)
                                    <span class="text-green-600 font-bold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Ativo
                        </span>
                                @else
                                    <span class="text-gray-500 font-medium">Pausado</span>
                                @endif
                            </div>

                            <form action="{{ route('mundos-de-mim.estilos.toggle') }}" method="POST">
                                @csrf
                                <input type="hidden" name="theme_id" value="{{ $theme->id }}">

                                <button type="submit"
                                        class="relative inline-flex h-10 w-20 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-4 focus:ring-indigo-600/30 {{ $isEnabled ? 'bg-indigo-600' : 'bg-gray-200' }}"
                                        role="switch">
                                    <span class="sr-only">Habilitar</span>
                                    <span aria-hidden="true"
                                          class="pointer-events-none inline-block h-9 w-9 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $isEnabled ? 'translate-x-10' : 'translate-x-0' }}">
                        </span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($themes->isEmpty())
                <div class="text-center py-20">
                    <p class="text-gray-500 text-lg">Nenhum universo disponível no momento. Volte em breve!</p>
                </div>
            @endif

        </div>
    </div>
</x-MundosDeMim::layout>
