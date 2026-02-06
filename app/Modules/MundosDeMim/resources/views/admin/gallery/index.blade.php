<x-MundosDeMim::layout>
    <div class="p-6">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-black text-white">Gerenciador de Galeria</h1>
                <p class="text-slate-400">Curadoria da Landing Page Pública</p>
            </div>
            <a href="{{ route('mundos-de-mim.landing') }}" target="_blank"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                Ver Landing Page <i class="fa-solid fa-external-link ml-2"></i>
            </a>
        </header>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 text-green-400 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 text-red-400 rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        <!-- Seção 1: Galeria Atual (Public) -->
        <section class="mb-12">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i class="fa-solid fa-eye text-[#a3e635]"></i> Imagens Atuais na Landing Page
            </h2>

            @if(count($publicImages) > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($publicImages as $img)
                        <div class="group relative rounded-2xl overflow-hidden border border-white/10 glass aspect-square">
                            <img src="{{ $img['url'] }}" class="w-full h-full object-cover">
                            <div
                                class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                                <form action="{{ route('mundos-de-mim.admin.gallery.delete') }}" method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja remover esta imagem da landing page?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="filename" value="{{ $img['filename'] }}">
                                    <button type="submit"
                                        class="bg-red-600 text-white p-3 rounded-xl hover:bg-red-700 transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center glass rounded-2xl border border-dashed border-white/20">
                    <p class="text-slate-400">Nenhuma imagem na galeria pública ainda.</p>
                </div>
            @endif
        </section>

        <!-- Seção 2: Sugestões dos Temas (Exemplos) -->
        <section>
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-[#a3e635]"></i> Escolher das Demonstrações dos Temas
            </h2>

            <div class="space-y-8">
                @foreach($themes as $theme)
                    @if($theme->examples->count() > 0)
                        <div class="glass p-6 rounded-3xl border border-white/10">
                            <h3
                                class="font-black text-white mb-4 uppercase tracking-wider text-sm border-b border-white/5 pb-2">
                                {{ $theme->name }}</h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-8 gap-4">
                                @foreach($theme->examples as $example)
                                    <div class="group relative rounded-xl overflow-hidden border border-white/10 aspect-square">
                                        <img src="{{ asset('storage/' . $example->image_path) }}"
                                            class="w-full h-full object-cover">
                                        <div
                                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                            <form action="{{ route('mundos-de-mim.admin.gallery.copy') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="example_id" value="{{ $example->id }}">
                                                <button type="submit"
                                                    class="bg-[#a3e635] text-black px-3 py-1 rounded-lg font-bold text-xs hover:scale-105 transition">
                                                    USAR NA LANDING
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    </div>
</x-MundosDeMim::layout>