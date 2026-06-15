<x-Bingo::layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-6">
        <div class="text-center max-w-lg mx-auto">
            <div class="text-7xl mb-4 float">🎉</div>
            <h1 class="text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-rose-500 via-amber-500 to-emerald-500 mb-2">
                BINGO!
            </h1>
            <p class="text-xl text-amber-700 mb-2 font-medium">
                Diversão para toda a família! 🦖🎨
            </p>
            <p class="text-amber-600/70 mb-8 text-sm">
                Escolha um tema, crie sua partida e jogue com amigos e família!
            </p>

            <a href="{{ route('bingo.create') }}"
               class="inline-block bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-xl font-bold px-10 py-5 rounded-2xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 mb-4">
                🎯 Criar Partida
            </a>

            @auth
                <br>
                <a href="{{ route('bingo.historico') }}"
                   class="inline-block text-amber-700 hover:text-amber-900 font-semibold text-sm mt-4 underline underline-offset-2 decoration-amber-300">
                    📋 Meu Histórico
                </a>
            @endauth
        </div>

        <div class="mt-12 grid grid-cols-3 gap-6 text-center">
            <div class="bg-white/70 rounded-2xl p-4 shadow-sm backdrop-blur">
                <div class="text-3xl mb-1">🎨</div>
                <div class="text-sm font-semibold text-amber-800">Temas Divertidos</div>
            </div>
            <div class="bg-white/70 rounded-2xl p-4 shadow-sm backdrop-blur">
                <div class="text-3xl mb-1">👨‍👩‍👧‍👦</div>
                <div class="text-sm font-semibold text-amber-800">Multiplayer</div>
            </div>
            <div class="bg-white/70 rounded-2xl p-4 shadow-sm backdrop-blur">
                <div class="text-3xl mb-1">🏆</div>
                <div class="text-sm font-semibold text-amber-800">Prêmios</div>
            </div>
        </div>
    </div>
</x-Bingo::layout>
