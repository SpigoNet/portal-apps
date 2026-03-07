<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - Mundos de Mim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Space Grotesk', sans-serif; }
        .bg-dark-tech {
            background-color: #1E1E24;
            background-image: radial-gradient(circle at top right, rgba(123, 44, 191, 0.15), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(0, 119, 182, 0.15), transparent 40%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-dark-tech text-white min-h-screen flex items-center justify-center p-6 relative">
    <!-- Back to Home -->
    <a href="{{ route('mundos-de-mim.landing') }}" class="absolute top-6 left-6 text-slate-400 hover:text-[#62A87C] transition flex items-center gap-2">
        &larr; Voltar
    </a>

    <div class="glass p-10 rounded-[2rem] w-full max-w-md shadow-2xl relative z-10">
        <div class="text-center mb-8">
            <span class="text-3xl font-black tracking-tighter font-heading block mb-2">MUNDOS DE <span class="text-[#62A87C]">MIM</span></span>
            <p class="text-slate-400 text-sm">Acesse sua galeria de universos</p>
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

        <form method="POST" action="{{ route('mundos-de-mim.login.submit') }}">
            @csrf
            
            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-slate-300 mb-2">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#62A87C] focus:ring-1 focus:ring-[#62A87C] transition">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Senha</label>
                <input type="password" id="password" name="password" required
                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#62A87C] focus:ring-1 focus:ring-[#62A87C] transition">
            </div>

            <div class="flex items-center justify-between mb-8">
                <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-white/10 bg-black/30 text-[#62A87C] focus:ring-[#62A87C]">
                    Lembrar-me
                </label>
            </div>

            <button type="submit" class="w-full bg-[#62A87C] text-black font-bold text-lg py-4 rounded-xl hover:shadow-[0_0_20px_rgba(98,168,124,0.4)] transition transform hover:-translate-y-0.5">
                Entrar
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-slate-400 border-t border-white/10 pt-6">
            Ainda não tem uma conta? 
            <a href="{{ route('mundos-de-mim.register') }}" class="text-[#3B9AB2] hover:text-[#62A87C] font-semibold transition">Criar conta</a>
        </div>
        
        <div class="mt-4 text-center text-xs text-slate-500">
            Ou use sua conta do portal principal
            <a href="{{ route('login') }}" class="text-white hover:text-[#62A87C] ml-1 transition">Login do Portal</a>
        </div>
    </div>
</body>
</html>
