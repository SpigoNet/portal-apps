<x-Bingo::layout>
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-black text-amber-800">📋 Meu Histórico</h1>
                <a href="{{ route('bingo.index') }}" class="text-sm text-amber-600 hover:text-amber-800 underline underline-offset-2">
                    ← Voltar
                </a>
            </div>

            @if($partidas->isEmpty())
                <div class="text-center bg-white/90 rounded-3xl p-12 shadow-lg border-2 border-amber-200">
                    <div class="text-6xl mb-4">🎲</div>
                    <p class="text-xl font-bold text-amber-700">Nenhuma partida ainda!</p>
                    <p class="text-amber-600/70 text-sm mt-1">Crie uma partida e comece a jogar</p>
                    <a href="{{ route('bingo.create') }}" class="inline-block mt-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold px-8 py-3 rounded-2xl shadow-lg hover:shadow-xl transition-all">
                        🚀 Criar Partida
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($partidas as $partida)
                        <div class="bg-white/90 rounded-2xl p-4 shadow border-2 border-amber-200 hover:border-amber-300 transition-all">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">🎯</span>
                                        <span class="font-bold text-amber-800">#{{ $partida->codigo }}</span>
                                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">
                                            {{ $partida->tema }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-amber-600/70 mt-1">
                                        🗓️ {{ $partida->created_at->format('d/m/Y H:i') }}
                                        · 👥 {{ $partida->jogadores_count }} jogadores
                                        · {{ $partida->status === 'finalizada' ? '✅ Finalizada' : ($partida->status === 'jogando' ? '🔄 Em andamento' : '⏳ Espera') }}
                                    </p>
                                </div>
                                <a href="{{ route('bingo.show', $partida->codigo) }}"
                                   class="text-sm bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 rounded-xl transition-all">
                                    Ver
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $partidas->links() }}
                </div>
            @endif
        </div>
    </div>
</x-Bingo::layout>
