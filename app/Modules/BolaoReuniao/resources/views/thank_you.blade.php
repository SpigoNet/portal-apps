@extends('BolaoReuniao::layouts.bolao')

@section('content')
    <div class="max-w-md w-full text-center space-y-8">
        <div class="glass p-8 rounded-3xl shadow-2xl space-y-6">
            <div class="text-center">
                <div id="clock" class="text-6xl font-bold tracking-tighter text-blue-400 mb-2">00:00:00</div>
                <div class="text-slate-400 font-medium">{{ now()->format('d/m/Y') }}</div>
            </div>

            <div
                class="bg-emerald-500/20 text-emerald-400 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-3xl font-bold italic">Chute Enviado!</h1>
            <p class="text-slate-400 text-lg">Aguarde a reunião encerrar para ver se você ganhou!</p>

            <div class="pt-6 border-t border-slate-800">
                <p class="text-sm text-slate-500 animate-pulse">A página irá atualizar automaticamente para o resultado...
                </p>
            </div>
        </div>
    </div>

    <script>
        // Digital Clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('pt-BR', { hour12: false });
            const clockEl = document.getElementById('clock');
            if (clockEl) {
                clockEl.textContent = time;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Polling Status
        setInterval(() => {
            fetch("{{ route('bolao.status', $meeting->id) }}")
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'closed') {
                        window.location.href = "{{ route('bolao.results', $meeting->id) }}";
                    }
                });
        }, 5000);
    </script>
@endsection