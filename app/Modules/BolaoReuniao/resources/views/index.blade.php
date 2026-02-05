@extends('BolaoReuniao::layouts.bolao')

@section('content')
    <div class="max-w-2xl w-full text-center space-y-8">
        @if(!$activeMeeting)
            <div class="glass p-8 rounded-3xl shadow-2xl">
                @auth
                    <h1 class="text-4xl font-bold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">
                        Novo Bolão</h1>
                    <form action="{{ route('bolao.start') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="text" name="name" placeholder="Nome da Reunião (ex: Reunião Semanal)" required
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-500/20">
                            Começar Reunião
                        </button>
                    </form>
                @else
                    <h1
                        class="text-4xl font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400 text-italic">
                        Bolão Fatec</h1>
                    <p class="text-slate-400">Nenhuma reunião iniciada no momento.</p>
                    <div id="clock" class="text-7xl font-bold tracking-tighter text-blue-400 my-8">00:00:00</div>
                    <p class="text-sm text-slate-500">Aguardando o início pelo administrador...</p>
                    <script>
                        function updateClock() {
                            const now = new Date();
                            const time = now.toLocaleTimeString('pt-BR', { hour12: false });
                            document.getElementById('clock').textContent = time;
                        }
                        setInterval(updateClock, 1000);
                        updateClock();

                        // Poll for new meeting every 10s
                        setInterval(() => window.location.reload(), 10000);
                    </script>
                @endauth
            </div>
        @else
            <div class="glass p-8 rounded-3xl shadow-2xl space-y-8">
                <div class="text-center">
                    <div id="clock" class="text-7xl font-bold tracking-tighter text-blue-400 mb-2">00:00:00</div>
                    <div class="text-slate-400 font-medium">{{ now()->format('d/m/Y') }}</div>
                </div>

                <div class="pt-4 border-t border-slate-800/50">
                    <h1
                        class="text-3xl font-bold mb-1 bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">
                        {{ $activeMeeting->name }}</h1>
                    <p class="text-slate-400">Escaneie o QR Code para participar!</p>
                </div>

                <div class="flex justify-center">
                    <div id="qrcode" class="p-4 bg-white rounded-2xl shadow-inner border-8 border-white"></div>
                </div>

                <div class="space-y-4">
                    <p class="text-xl font-semibold">{{ $activeMeeting->guesses->count() }} pessoas participando</p>
                    @if(auth()->check() && (empty($activeMeeting->user_id) || auth()->id() === $activeMeeting->user_id))
                        <form action="{{ route('bolao.end', $activeMeeting->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-red-500/20">
                                Encerrar Reunião
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
                // Digital Clock
                function updateClock() {
                    const now = new Date();
                    const time = now.toLocaleTimeString('pt-BR', { hour12: false });
                    document.getElementById('clock').textContent = time;
                }
                setInterval(updateClock, 1000);
                updateClock();

                // QR Code
                new QRCode(document.getElementById("qrcode"), {
                    text: "{{ route('bolao.participate', $activeMeeting->id) }}",
                    width: 256,
                    height: 256,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });

                // Polling Status
                setInterval(() => {
                    fetch("{{ route('bolao.status', $activeMeeting->id) }}")
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'closed') {
                                window.location.href = "{{ route('bolao.results', $activeMeeting->id) }}";
                            }
                        });
                }, 5000);
            </script>
        @endif
    </div>
@endsection