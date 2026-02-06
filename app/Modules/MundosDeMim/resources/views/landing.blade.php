<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mundos de Mim - Sua Vida em Arte Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
        }

        .bg-gradient-spigo {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        }

        .text-gradient {
            background: linear-gradient(to right, #8b5cf6, #d946ef, #a3e635);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23a3e635' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>

<body class="bg-gradient-spigo text-white min-h-screen">

    <div class="hero-pattern absolute inset-0 z-0"></div>

    {{-- Header --}}
    <nav class="relative z-10 px-6 py-4 flex justify-between items-center max-w-7xl mx-auto">
        <div class="flex items-center gap-2">
            <span class="text-2xl font-black tracking-tighter">MUNDOS DE <span class="text-[#a3e635]">MIM</span></span>
        </div>
        <div class="flex gap-4 items-center">
            @auth
                <a href="{{ route('mundos-de-mim.index') }}"
                    class="text-sm font-semibold hover:text-[#a3e635] transition">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-semibold hover:text-[#a3e635] transition">Entrar</a>
                <a href="{{ route('register') }}"
                    class="bg-[#a3e635] text-black px-5 py-2 rounded-full font-bold text-sm hover:scale-105 transition transform">Começar
                    Agora</a>
            @endauth
        </div>
    </nav>

    {{-- Hero --}}
    <main class="relative z-10 px-6 pt-20 pb-16 text-center max-w-7xl mx-auto">
        <div
            class="inline-block px-4 py-1.5 mb-6 rounded-full bg-[#a3e635]/10 border border-[#a3e635]/20 text-[#a3e635] text-sm font-bold tracking-wide uppercase">
            A Nova Fronteira da Arte Digital
        </div>
        <h1 class="text-6xl md:text-8xl font-extrabold mb-8 leading-tight tracking-tighter">
            Sua essência em <br><span class="text-gradient">universos infinitos</span>.
        </h1>
        <p class="text-lg md:text-2xl text-slate-400 mb-12 max-w-4xl mx-auto leading-relaxed">
            Mundos de Mim usa IA generativa de ponta para criar artes digitais únicas. <br class="hidden md:block"> Todo
            dia uma nova versão de você em cenários que desafiam a realidade.
        </p>

        <div class="flex flex-wrap justify-center gap-6 mb-24">
            <a href="#planos"
                class="bg-[#a3e635] text-black px-10 py-5 rounded-2xl font-black text-xl hover:shadow-[0_0_30px_rgba(163,230,53,0.5)] transition hover:scale-105 transform">VER
                PLANOS</a>
            <a href="{{ route('register') }}"
                class="glass border border-white/20 px-10 py-5 rounded-2xl font-black text-xl hover:bg-white/10 transition flex items-center gap-3">
                COMEÇAR AGORA <i class="fa-solid fa-bolt text-[#a3e635]"></i>
            </a>
        </div>

        <div class="relative max-w-6xl mx-auto mt-20">
            @if(count($images) > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @php
                        // Divide as imagens em 4 colunas para manter o efeito staggered
                        $columns = [[], [], [], []];
                        foreach ($images as $index => $image) {
                            $columns[$index % 4][] = $image;
                        }
                    @endphp

                    @foreach($columns as $colIndex => $columnImages)
                        <div class="space-y-4 md:space-y-6 {{ $colIndex % 2 !== 0 ? 'pt-8 md:pt-12' : '' }}">
                            @foreach($columnImages as $image)
                                <img src="{{ $image }}"
                                    class="rounded-3xl shadow-2xl hover:scale-105 transition transform duration-500 border border-white/10"
                                    alt="Arte Curada Mundos de Mim" title="Explore novos horizontes com Mundos de Mim">
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-20 text-center glass rounded-3xl border border-white/10">
                    <p class="text-slate-400">Nossa galeria de sonhos está sendo preparada. Volte em breve!</p>
                </div>
            @endif
            <div
                class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-transparent to-transparent pointer-events-none">
            </div>
        </div>
        <div
            class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-transparent to-transparent pointer-events-none">
        </div>
        </div>
    </main>

    {{-- Features Grid --}}
    <section class="relative z-10 px-6 py-20 bg-black/30 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="glass p-8 rounded-3xl border border-white/5 hover:border-[#a3e635]/30 transition group">
                <div
                    class="w-12 h-12 bg-[#a3e635]/10 rounded-xl flex items-center justify-center text-[#a3e635] mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-wand-magic-sparkles text-xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Artes Diárias</h3>
                <p class="text-slate-400">Uma nova surpresa todo dia. Nossa IA cria cenários baseados na sua biometria e
                    preferências.</p>
            </div>
            <div class="glass p-8 rounded-3xl border border-white/5 hover:border-[#a3e635]/30 transition group">
                <div
                    class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-400 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Você e Criaturas</h3>
                <p class="text-slate-400">Adicione seu parceiro(a) ou filhos e receba artes memoráveis em família.</p>
            </div>
            <div class="glass p-8 rounded-3xl border border-white/5 hover:border-[#a3e635]/30 transition group">
                <div
                    class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-400 mb-6 group-hover:scale-110 transition-transform">
                    <i class="fa-brands fa-whatsapp text-xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Direto no Celular</h3>
                <p class="text-slate-400">Assine o plano Prime e receba as artes diretamente no seu WhatsApp sem
                    complicações.</p>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="planos" class="relative z-10 px-6 py-24 max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold mb-4">Escolha o seu Mundo</h2>
            <p class="text-slate-400">Planos flexíveis para quem ama arte e tecnologia.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            {{-- Eco --}}
            <div class="glass p-10 rounded-[3rem] border border-white/5 relative overflow-hidden group">
                <div
                    class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500/10 blur-3xl group-hover:bg-blue-500/20 transition-colors">
                </div>
                <h3 class="text-2xl font-bold mb-2">Plano ECO</h3>
                <div class="flex items-baseline gap-1 mb-6">
                    <span class="text-4xl font-extrabold text-[#a3e635]">R$ 14,90</span>
                    <span class="text-slate-400">/mês</span>
                </div>
                <ul class="space-y-4 mb-10 text-slate-300">
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-[#a3e635]"></i> 1 Arte por dia
                    </li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-[#a3e635]"></i> Entrega via
                        <strong>Telegram</strong>
                    </li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-[#a3e635]"></i> 5 Créditos
                        semanais sob demanda</li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-[#a3e635]"></i> Galeria Online
                        Completa</li>
                </ul>
                <a href="{{ route('register') }}"
                    class="block w-full text-center bg-white/5 hover:bg-white/10 border border-white/10 py-4 rounded-2xl font-bold transition">Começar
                    Agora</a>
            </div>

            {{-- Prime --}}
            <div
                class="bg-white text-black p-10 rounded-[3rem] relative shadow-[0_0_40px_rgba(163,230,53,0.3)] transform scale-105 border-4 border-[#a3e635]">
                <div
                    class="absolute top-6 right-8 bg-[#a3e635] text-black text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-tighter">
                    Recomendado</div>
                <h3 class="text-2xl font-bold mb-2">Plano PRIME</h3>
                <div class="flex items-baseline gap-1 mb-6">
                    <span class="text-4xl font-extrabold">R$ 34,90</span>
                    <span class="text-slate-600">/mês</span>
                </div>
                <ul class="space-y-4 mb-10 text-slate-700">
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-600"></i> 1 Arte por dia
                    </li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-600"></i> Entrega via
                        <strong>WhatsApp API</strong>
                    </li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-600"></i> 5 Créditos
                        semanais sob demanda</li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-600"></i> Temas Sazonais
                        Premium</li>
                    <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-600"></i> Suporte
                        Prioritário</li>
                </ul>
                <a href="{{ route('register') }}"
                    class="block w-full text-center bg-black text-white py-4 rounded-2xl font-bold hover:bg-slate-800 transition">Assinar
                    Agora</a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="relative z-10 border-t border-white/5 py-12 px-6">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-sm text-slate-500">
                &copy; 2026 Mundos de Mim. Powered by Spigo.net IA Tools.
            </div>
            <div class="flex gap-6 text-slate-400">
                <a href="#" class="hover:text-white transition">Privacidade</a>
                <a href="#" class="hover:text-white transition">Termos de Uso</a>
                <a href="#" class="hover:text-white transition">Ajuda</a>
            </div>
        </div>
    </footer>

</body>

</html>