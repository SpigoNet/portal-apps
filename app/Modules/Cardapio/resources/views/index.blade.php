@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
<x-Cardapio::layout titulo="Início">
    <h1 class="text-2xl font-extrabold">{{ $casa->nome }}</h1>

    <section class="cd-card space-y-2">
        <h2 class="font-extrabold text-lg">Hoje</h2>
        @forelse ($hoje as $refeicao)
            <div class="flex flex-wrap items-baseline gap-2">
                <span class="cd-linha-rotulo">{{ $rotulos[$refeicao->tipo_refeicao] }}</span>
                <span>{{ $refeicao->receita?->nome ?? '— sem receita —' }}</span>
                <span class="text-sm">({{ $refeicao->pessoas->pluck('nome')->join(', ') }})</span>
                @if ($refeicao->consumida()) <span class="cd-chip">✔ feita</span> @endif
            </div>
        @empty
            <p class="text-sm">Nenhuma refeição planejada para hoje.
                @if ($planoAtual) <a class="underline font-bold" href="{{ route('cardapio.planejamento.show', $planoAtual) }}">Abrir a semana</a>
                @else <a class="underline font-bold" href="{{ route('cardapio.planejamento.index') }}">Planejar a semana</a> @endif
            </p>
        @endforelse
    </section>

    @if ($eventosProximos->isNotEmpty() || $ausenciasProximas->isNotEmpty())
        <section class="cd-card space-y-1 text-sm">
            <h2 class="font-extrabold text-lg">Próximos dias</h2>
            @foreach ($eventosProximos as $e)
                <div>📅 {{ $e->data->format('d/m') }} — {{ $e->titulo }}
                    <span class="cd-chip">{{ $e->tipo === 'fora' ? 'fora de casa' : '+'.$e->pessoas_extras.' pessoa(s)' }}</span></div>
            @endforeach
            @foreach ($ausenciasProximas as $a)
                <div>🧳 {{ $a->pessoa->nome }} ausente de {{ $a->inicio->format('d/m') }} a {{ $a->fim->format('d/m') }}@if ($a->motivo) ({{ $a->motivo }}) @endif</div>
            @endforeach
            <a class="underline font-bold" href="{{ route('cardapio.agenda.index') }}">Abrir agenda</a>
        </section>
    @endif

    <section class="cd-card space-y-1">
        <h2 class="font-extrabold text-lg">Alertas</h2>
        @if ($vencendo->isNotEmpty())
            <a href="{{ route('cardapio.estoque.index') }}" class="block">⚠ {{ $vencendo->count() }} produto(s) vencidos ou perto do vencimento</a>
        @endif
        @if ($estoqueBaixo->isNotEmpty())
            <a href="{{ route('cardapio.estoque.index') }}" class="block">📉 {{ $estoqueBaixo->count() }} produto(s) com estoque baixo</a>
        @endif
        @if ($itensParaComprar > 0)
            <a href="{{ route('cardapio.compras.index') }}" class="block">🛒 {{ $itensParaComprar }} item(ns) para comprar</a>
        @endif
        @unless ($proximoPlano)
            <form method="POST" action="{{ route('cardapio.planejamento.store') }}">@csrf
                <input type="hidden" name="semana" value="{{ $proximaSemana->toDateString() }}">
                🍲 Cardápio da próxima semana pendente — <button class="underline font-bold" type="submit">planejar agora</button>
            </form>
        @endunless
        @if ($vencendo->isEmpty() && $estoqueBaixo->isEmpty() && $itensParaComprar === 0 && $proximoPlano)
            <p class="text-sm">Tudo em dia. ✅</p>
        @endif
    </section>

    <div class="grid sm:grid-cols-3 gap-4">
        <a href="{{ route('cardapio.pessoas.index') }}" class="cd-card block hover:border-green-500">
            <div class="text-sm font-bold text-green-700">Pessoas</div>
            <div class="text-3xl font-extrabold">{{ $pessoas->count() }}</div>
            <div class="text-sm">{{ $pessoas->whereNotNull('user_id')->count() }} com login · {{ $pessoas->whereNull('user_id')->count() }} sem login</div>
        </a>
        <a href="{{ route('cardapio.estoque.index') }}" class="cd-card block hover:border-green-500">
            <div class="text-sm font-bold text-green-700">Estoque</div>
            <div class="text-3xl font-extrabold">{{ $totalEstoque }}</div>
            <div class="text-sm">alimentos diferentes</div>
        </a>
        <a href="{{ route('cardapio.receitas.index') }}" class="cd-card block hover:border-green-500">
            <div class="text-sm font-bold text-green-700">Receitas</div>
            <div class="text-3xl font-extrabold">{{ $casa->receitas()->count() }}</div>
            <div class="text-sm">{{ $casa->receitas()->where('favorita', true)->count() }} favoritas</div>
        </a>
    </div>
</x-Cardapio::layout>
