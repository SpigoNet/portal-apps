<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - ANT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Space Grotesk', sans-serif; }
        .bg-ant {
            background-color: #1A1F2E;
            background-image: radial-gradient(circle at top left, rgba(99, 102, 241, 0.15), transparent 40%),
                              radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.10), transparent 40%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-ant text-white min-h-screen flex items-center justify-center p-6 relative">
    <!-- Back to Portal -->
    <a href="{{ route('welcome') }}" class="absolute top-6 left-6 text-slate-400 hover:text-indigo-400 transition flex items-center gap-2 text-sm">
        &larr; Portal
    </a>

    <div class="glass p-10 rounded-[2rem] w-full max-w-md shadow-2xl relative z-10 my-8">
        <div class="text-center mb-8">
            <span class="text-3xl font-black tracking-tighter font-heading block mb-2">
                <span class="text-indigo-400">ANT</span>
            </span>
            <p class="text-slate-400 text-sm">Área de Notas e Trabalhos</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('ant.register.submit') }}">
            @csrf

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-slate-300 mb-2">Nome Completo</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 transition">
            </div>

            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-slate-300 mb-2">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 transition">
            </div>

            <div class="mb-5">
                <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Senha</label>
                <input type="password" id="password" name="password" required
                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 transition">
            </div>

            <div class="mb-8">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-300 mb-2">Confirmar Senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 transition">
            </div>

            <button type="submit" class="w-full bg-emerald-600 text-white font-bold text-lg py-4 rounded-xl hover:bg-emerald-500 hover:shadow-[0_0_20px_rgba(16,185,129,0.4)] transition transform hover:-translate-y-0.5">
                Criar Conta
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-slate-400 border-t border-white/10 pt-6">
            Já tem uma conta?
            <a href="{{ route('ant.login') }}" class="text-indigo-400 hover:text-indigo-300 font-semibold transition ml-1">Fazer login</a>
        </div>
    </div>
</body>
</html>
