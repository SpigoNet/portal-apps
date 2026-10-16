@extends('Pidgey::components.layout')

@section('content')
    <div class="space-y-8">
        <header class="border-b border-white/10 pb-4">
            <h1 class="text-2xl font-black tracking-tight text-amber-400">
                <i class="fa-brands fa-telegram mr-2"></i>Telegram
            </h1>
            <p class="text-sm text-slate-400 mt-1">Cada módulo tem o seu bot, com a sua personalidade. Vincule o seu Telegram uma vez e converse com todos.</p>
        </header>

        <section class="rounded-2xl border border-white/10 bg-white/5 p-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-200">Seu vínculo</h2>

            @if ($vinculo)
                <p class="text-sm text-emerald-300">✅ Vinculado{{ $vinculo->username ? ' como @'.$vinculo->username : '' }}.</p>
                <form method="POST" action="{{ route('pidgey.telegram.desvincular') }}" onsubmit="return confirm('Desvincular o Telegram?')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg bg-red-500/20 px-4 py-2 text-sm font-bold text-red-300" type="submit">Desvincular</button>
                </form>
            @else
                <p class="text-sm text-slate-400">Gere um código, abra um dos bots abaixo e envie <code class="text-amber-300">/start CÓDIGO</code>.</p>
                @if ($codigo)
                    <div class="rounded-xl border border-amber-400/40 bg-amber-400/10 p-4">
                        <div class="text-xs text-slate-400">Código (vale {{ $minutos }} minutos, uso único)</div>
                        <div class="text-3xl font-black tracking-widest text-amber-300 select-all">{{ $codigo }}</div>
                    </div>
                @endif
                <form method="POST" action="{{ route('pidgey.telegram.codigo') }}">
                    @csrf
                    <button class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-black text-slate-900" type="submit">Gerar código</button>
                </form>
            @endif
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/5 p-6 space-y-3">
            <h2 class="text-lg font-bold text-slate-200">Bots</h2>
            @forelse ($bots as $b)
                <div class="flex items-center justify-between rounded-xl bg-white/5 px-4 py-3">
                    <div>
                        <div class="font-bold">{{ $b['persona']->name }}</div>
                        <div class="text-xs text-slate-400">{{ $b['bot'] ?? 'bot sem resposta do Telegram' }}</div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400">Nenhum bot configurado. Preencha o token do bot na persona (Alfred) e rode <code>php artisan pidgey:telegram-webhook SLUG</code>.</p>
            @endforelse
        </section>
    </div>
@endsection
