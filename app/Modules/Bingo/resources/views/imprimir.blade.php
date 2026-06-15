<x-Bingo::layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-6" x-data="printForm()">
        <div class="w-full max-w-lg mx-auto bg-white/90 backdrop-blur rounded-3xl p-8 shadow-xl border border-amber-200">
            <div class="text-center mb-6">
                <div class="text-5xl mb-2">🖨️</div>
                <h1 class="text-3xl font-black text-amber-800">Imprimir Jogo</h1>
                <p class="text-amber-600/70 text-sm">Gere cartelas para imprimir e jogar offline</p>
            </div>

            <form method="POST" action="{{ route('bingo.imprimir-gerar') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-amber-800 mb-3">🎨 Tema</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($temas as $tema)
                            <label class="cursor-pointer">
                                <input type="radio" name="tema" value="{{ $tema }}"
                                       class="sr-only peer" required>
                                <div class="p-3 rounded-2xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 text-center transition-all duration-200 peer-checked:ring-4 peer-checked:ring-emerald-400 peer-checked:border-emerald-500 peer-checked:scale-105">
                                    <div class="text-2xl mb-1">🖼️</div>
                                    <div class="text-sm font-bold text-amber-800">{{ str_replace('.png', '', $tema) }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-amber-800 mb-2">📄 Quantidade de cartelas</label>
                    <input type="number" name="cartelas" min="1" max="30" value="6"
                           class="w-full text-center text-lg px-4 py-3 rounded-2xl border-2 border-amber-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-300 outline-none font-bold">
                    <p class="text-xs text-amber-500 mt-1">Máximo 30 cartelas por página</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-amber-800 mb-3">📐 Tamanho da cartela</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="tamanho" value="pequena"
                                   class="sr-only peer" checked>
                            <div class="p-3 rounded-2xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 text-center transition-all duration-200 peer-checked:ring-4 peer-checked:ring-emerald-400 peer-checked:border-emerald-500 peer-checked:scale-105">
                                <div class="text-xl mb-1">🔹</div>
                                <div class="text-xs font-bold text-amber-800">Pequena</div>
                                <div class="text-[10px] text-amber-500">3 colunas</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="tamanho" value="media"
                                   class="sr-only peer">
                            <div class="p-3 rounded-2xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 text-center transition-all duration-200 peer-checked:ring-4 peer-checked:ring-emerald-400 peer-checked:border-emerald-500 peer-checked:scale-105">
                                <div class="text-xl mb-1">🔶</div>
                                <div class="text-xs font-bold text-amber-800">Média</div>
                                <div class="text-[10px] text-amber-500">2 colunas</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="tamanho" value="grande"
                                   class="sr-only peer">
                            <div class="p-3 rounded-2xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 text-center transition-all duration-200 peer-checked:ring-4 peer-checked:ring-emerald-400 peer-checked:border-emerald-500 peer-checked:scale-105">
                                <div class="text-xl mb-1">⬜</div>
                                <div class="text-xs font-bold text-amber-800">Grande</div>
                                <div class="text-[10px] text-amber-500">1 coluna</div>
                            </div>
                        </label>
                    </div>
                </div>

                <label class="flex items-center gap-3 p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl border border-amber-200 cursor-pointer">
                    <input type="checkbox" name="recortar" value="1"
                           class="w-5 h-5 accent-amber-500 rounded">
                    <div>
                        <span class="text-sm font-bold text-amber-800 block">✂️ Incluir números para recortar</span>
                        <span class="text-xs text-amber-600/70">Os 25 elementos para colocar em um saquinho de sorteio</span>
                    </div>
                </label>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold py-4 px-6 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200 text-lg">
                    🖨️ Gerar para Impressão
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('bingo.index') }}" class="text-sm text-amber-600 hover:text-amber-800 underline underline-offset-2">
                    ← Voltar
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('printForm', () => ({}));
        });
    </script>
    @endpush
</x-Bingo::layout>
