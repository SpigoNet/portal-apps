@php
    use App\Modules\TreeTask\Support\Aparencia;
@endphp
<x-TreeTask::layout titulo="Tarefa concluída" :bare="true">
    <style>
        .tt-fest { position: relative; min-height: 100vh; background: radial-gradient(900px 500px at 50% -10%, #3A2457 0%, #17121D 70%); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 24px 16px; }
        .tt-fest canvas { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
        .tt-fest-card { position: relative; z-index: 1; width: 100%; max-width: 680px; background: #fff; border-radius: 28px; padding: 40px clamp(20px, 5vw, 44px); text-align: center; box-shadow: 0 30px 80px rgba(0, 0, 0, .4); animation: tt-sobe .6s ease-out both; }
        @keyframes tt-sobe { from { opacity: 0; transform: translate3d(0, 40px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .tt-fest-sel { width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 16px; display: grid; place-items: center; background: var(--tt-lime); color: #2B1B3D; box-shadow: 0 10px 24px rgba(120, 150, 20, .35); }
        .tt-sugs { display: flex; flex-direction: column; gap: 8px; max-height: 280px; overflow-y: auto; text-align: left; }
    </style>

    <div class="tt-fest">
        <canvas id="fireworks-canvas" aria-hidden="true"></canvas>

        <div class="tt-fest-card">
            <div class="tt-fest-sel">
                <svg class="tt-i l" viewBox="0 0 24 24" aria-hidden="true" style="width:34px;height:34px;stroke-width:2.4"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
            </div>
            <h1 style="font-size:30px;font-weight:800;letter-spacing:-.02em">Tarefa concluída!</h1>
            <p class="tt-muted" style="margin:10px 0 2px;font-size:16px">Você finalizou</p>
            <h2 style="font-size:22px;font-weight:700;color:var(--tt-brand-d)">{{ $tarefa->titulo }}</h2>
            <p class="tt-muted" style="margin:4px 0 0;font-size:13px">{{ $tarefa->fase->projeto->nome }} › {{ $tarefa->fase->nome }}</p>

            <hr style="border:0;border-top:1px solid var(--tt-line);margin:26px 0">

            <h3 style="font-size:18px;font-weight:700;margin-bottom:14px">O que fazer agora?</h3>
            <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:24px">
                <a href="{{ route('treetask.focus.index') }}" class="tt-btn">Nada por enquanto</a>
                <a href="{{ route('treetask.index') }}" class="tt-btn">Procurar em outro projeto</a>
            </div>

            <div style="background:var(--tt-brand-soft);border-radius:18px;padding:18px;text-align:left">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:8px">
                    <span class="tt-sec-tit" style="color:#4B2A72">Próximas nesta fase · {{ $tarefa->fase->nome }}</span>
                    <span class="tt-chip" style="background:#fff;color:#4B2A72">{{ $sugestoes->count() }}</span>
                </div>

                @if($sugestoes->count() > 0)
                    <div class="tt-sugs">
                        @foreach($sugestoes as $sugestao)
                            <div class="tt-card" style="padding:10px 12px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:10px;box-shadow:none">
                                <span style="display:flex;align-items:center;gap:10px;min-width:0;flex:1 1 180px">
                                    <span class="tt-prio {{ Aparencia::prioridadeClasse($sugestao->prioridade) }}" style="font-size:0" title="Prioridade: {{ $sugestao->prioridade }}"></span>
                                    <span style="font-weight:600;overflow-wrap:anywhere">{{ $sugestao->titulo }}</span>
                                </span>
                                <span style="display:flex;gap:8px">
                                    <form action="{{ route('treetask.tarefas.updateStatus', $sugestao->id_tarefa) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="Em Andamento">
                                        <button type="submit" class="tt-btn pri sm">Iniciar</button>
                                    </form>
                                    <a class="tt-btn sm" href="{{ route('treetask.tarefas.edit', ['id' => $sugestao->id_tarefa, 'origin' => 'focus']) }}">Ver</a>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="tt-muted" style="margin:0;text-align:center;font-style:italic">Não há mais tarefas pendentes nesta fase. Bom trabalho!</p>
                @endif
            </div>

            <div style="margin-top:22px">
                <a href="{{ route('treetask.focus.index') }}" class="tt-btn ghost">Voltar ao modo foco</a>
            </div>
        </div>
    </div>

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
                const colors = ['#C9E86B', '#A47BC7', '#FFFFFF', '#8A58BD', '#E0CBF5', '#DDF58E'];
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
</x-TreeTask::layout>
