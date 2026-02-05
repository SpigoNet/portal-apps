@extends('BolaoReuniao::layouts.bolao')

@section('content')
    <div class="max-w-3xl w-full glass p-8 rounded-3xl shadow-2xl space-y-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">
                Resultados do Bolão</h1>
            <p class="text-slate-400 mt-2">{{ $meeting->name }} (Encerrada em {{ $meeting->finished_at->format('H:i:s') }})
            </p>
        </div>

        <div class="space-y-4">
            @forelse($meeting->guesses as $index => $guess)
                @if($index === 0)
                    <div
                        class="relative p-8 rounded-3xl bg-gradient-to-br from-blue-600/30 to-emerald-600/30 border border-blue-500/50 shadow-2xl shadow-blue-500/20 overflow-hidden">
                        <div
                            class="absolute -top-4 -right-4 bg-yellow-400 text-slate-950 font-black px-8 py-4 rotate-12 text-xl shadow-lg">
                            CAMPEÃO!
                        </div>
                        <div class="flex items-center space-x-6">
                            <div
                                class="w-20 h-20 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-3xl font-black text-slate-950 shadow-lg">
                                1º
                            </div>
                            <div>
                                <p class="text-4xl font-black tracking-tight">{{ $guess->name }}</p>
                                <div class="flex items-center space-x-3 mt-1">
                                    <span
                                        class="bg-blue-500/20 text-blue-300 px-3 py-1 rounded-full text-sm font-bold border border-blue-500/30">Chute:
                                        {{ $guess->guess }}</span>
                                    <span class="text-emerald-400 font-bold text-lg">
                                        @if($guess->diff_seconds < 60)
                                            {{ $guess->diff_seconds }}s de diferença
                                        @else
                                            {{ floor($guess->diff_seconds / 60) }}m {{ $guess->diff_seconds % 60 }}s
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div
                        class="flex items-center justify-between p-4 rounded-2xl bg-slate-900 border border-slate-800 transition-transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-4">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                {{ $index + 1 }}º
                            </div>
                            <div>
                                <p class="font-bold text-lg text-slate-100">{{ $guess->name }}</p>
                                <p class="text-sm text-slate-400">Chute: {{ $guess->guess }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-300">
                                @if($guess->diff_seconds < 60)
                                    {{ $guess->diff_seconds }}s
                                @else
                                    {{ floor($guess->diff_seconds / 60) }}m {{ $guess->diff_seconds % 60 }}s
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-center py-12 text-slate-500">
                    Ninguém participou deste bolão :(
                </div>
            @endforelse
        </div>

        <div class="pt-6 border-t border-slate-800 flex justify-center">
            <a href="{{ route('bolao.index') }}"
                class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-8 rounded-xl transition-all border border-slate-700">
                Voltar ao Início
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        @if($meeting->guesses->count() > 0)
            const duration = 3 * 1000;
            const animationEnd = Date.now() + duration;
            const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            const interval = setInterval(function () {
                const timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                const particleCount = 50 * (timeLeft / duration);
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        @endif
    </script>
@endsection