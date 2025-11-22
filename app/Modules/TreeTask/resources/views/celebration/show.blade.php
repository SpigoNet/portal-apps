<x-app-layout>
    <div class="relative min-h-screen bg-gray-900 flex items-center justify-center overflow-hidden">

        <canvas id="fireworks-canvas" class="absolute inset-0 z-0 pointer-events-none"></canvas>

        <div class="relative z-10 bg-white w-full max-w-2xl rounded-2xl shadow-2xl p-8 m-4 text-center animate-fade-in-up">

            <div class="mb-6">
                <div class="text-7xl mb-2 animate-bounce">🎉</div>
                <h1 class="text-3xl font-black text-gray-800 uppercase tracking-wide">Tarefa Concluída!</h1>
                <p class="text-gray-500 mt-2 text-lg">Você finalizou:</p>
                <h2 class="text-2xl font-bold text-blue-600 mt-1">{{ $tarefa->titulo }}</h2>
                <p class="text-sm text-gray-400 mt-1">{{ $tarefa->fase->projeto->nome }} > {{ $tarefa->fase->nome }}</p>
            </div>

            <hr class="border-gray-200 my-6">

            <div class="text-center">
                <h3 class="text-xl font-bold text-gray-700 mb-4 flex justify-center sm:justify-center">
                    <span class="mr-2">🤔</span> O que fazer agora?
                </h3>

                <div class="flex flex-wrap gap-3 mb-8 justify-center sm:justify-center">
                    <a href="{{ route('treetask.focus.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-full transition transform hover:scale-105">
                        ☕ Nada por enquanto
                    </a>
                    <a href="{{ route('treetask.index') }}" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-bold py-2 px-6 rounded-full transition transform hover:scale-105">
                        🔍 Procurar em outro projeto...
                    </a>
                </div>

                <div class="bg-blue-50 rounded-xl p-5 border border-blue-100">
                    <h4 class="text-sm font-bold text-blue-800 uppercase tracking-wider mb-3 flex justify-between items-center">
                        <span>Próximas nesta fase ({{ $tarefa->fase->nome }})</span>
                        <span class="bg-blue-200 text-blue-800 text-xs py-0.5 px-2 rounded-full">{{ $sugestoes->count() }}</span>
                    </h4>

                    @if($sugestoes->count() > 0)
                        <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($sugestoes as $sugestao)
                                <div class="bg-white p-3 rounded-lg shadow-sm border border-blue-100 flex justify-between items-center hover:shadow-md transition group">
                                    <div class="flex items-center overflow-hidden">
                                        @php
                                            $cor = match($sugestao->prioridade) {
                                                'Urgente' => 'bg-red-500',
                                                'Alta' => 'bg-orange-400',
                                                'Média' => 'bg-blue-400',
                                                default => 'bg-gray-300'
                                            };
                                        @endphp
                                        <span class="w-3 h-3 rounded-full {{ $cor }} mr-3 flex-shrink-0" title="Prioridade: {{ $sugestao->prioridade }}"></span>

                                        <div class="truncate">
                                            <span class="text-gray-800 font-medium block truncate">{{ $sugestao->titulo }}</span>
                                        </div>
                                    </div>

                                    <div class="flex space-x-2 flex-shrink-0 ml-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                        <form action="{{ route('treetask.tarefas.updateStatus', $sugestao->id_tarefa) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="Em Andamento">
                                            <button type="submit" style="line-height: 2rem;" class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold py-1.5 px-3 rounded transition">
                                                Iniciar
                                            </button>
                                        </form>

                                        <a style="line-height: 2rem;" href="{{ route('treetask.tarefas.edit', ['id' => $sugestao->id_tarefa, 'origin' => 'focus']) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold py-1.5 px-3 rounded transition">
                                            Ver
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-blue-400 italic text-sm">
                            Não há mais tarefas pendentes nesta fase! Bom trabalho! 👏
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('treetask.focus.index') }}" style="line-height: 2rem;display: inline-block;" class="mb-6 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold py-1.5 px-3 rounded transition">Voltar ao Modo Zen</a>
            </div>
        </div>
    </div>

    <style>
        /* Animação simples de entrada */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translate3d(0, 40px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        /* Scrollbar bonita para a lista */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #eff6ff; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #bfdbfe; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #60a5fa; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('fireworks-canvas');
            const ctx = canvas.getContext('2d');
            let particles = [];

            function resizeCanvas() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            function createParticle(x, y) {
                const colors = ['#ff4d4d', '#4dff4d', '#4d4dff', '#ffff4d', '#ff4dff', '#4dffff'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                const count = 40; // Mais partículas por explosão
                for (let i = 0; i < count; i++) {
                    const angle = (Math.PI * 2 * i) / count;
                    const velocity = Math.random() * 6 + 2;
                    particles.push({
                        x: x, y: y,
                        color: color,
                        radius: Math.random() * 3 + 1,
                        velocity: {
                            x: Math.cos(angle) * velocity,
                            y: Math.sin(angle) * velocity
                        },
                        alpha: 1,
                        decay: Math.random() * 0.015 + 0.005
                    });
                }
            }

            function animate() {
                // Rastro suave
                ctx.globalCompositeOperation = 'destination-out';
                ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.globalCompositeOperation = 'lighter';

                particles.forEach((p, index) => {
                    if (p.alpha <= 0) {
                        particles.splice(index, 1);
                    } else {
                        p.x += p.velocity.x;
                        p.y += p.velocity.y;
                        p.velocity.y += 0.05; // Gravidade
                        p.velocity.x *= 0.96; // Atrito do ar
                        p.velocity.y *= 0.96;
                        p.alpha -= p.decay;

                        ctx.beginPath();
                        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2, false);
                        ctx.fillStyle = p.color; // Usar cor sólida com alpha via globalAlpha se necessário, ou string rgba
                        ctx.globalAlpha = p.alpha;
                        ctx.fill();
                        ctx.globalAlpha = 1.0;
                    }
                });

                requestAnimationFrame(animate);
            }

            // Loop de fogos aleatórios
            function launchRandomFirework() {
                createParticle(
                    Math.random() * canvas.width,
                    Math.random() * (canvas.height * 0.6) // Explodir na parte superior
                );

                // Próximo fogo em tempo aleatório
                setTimeout(launchRandomFirework, Math.random() * 1000 + 200);
            }

            animate();

            // Iniciar sequencia inicial intensa
            launchRandomFirework();
            launchRandomFirework();
            launchRandomFirework();
        });
    </script>
</x-app-layout>
