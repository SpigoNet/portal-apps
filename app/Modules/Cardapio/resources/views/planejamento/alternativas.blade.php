@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
<x-Cardapio::layout titulo="Trocar receita">
    <div>
        <a class="text-sm underline" href="{{ route('cardapio.planejamento.show', $plano) }}">← Semana</a>
        <h1 class="text-2xl font-extrabold">Trocar: {{ $rotulos[$refeicao->tipo_refeicao] }} de {{ $refeicao->data->format('d/m') }}</h1>
        <p class="text-sm">Para {{ $refeicao->pessoas->pluck('nome')->join(', ') ?: 'ninguém' }}.
            Atual: <strong>{{ $refeicao->receita?->nome ?? 'nenhuma' }}</strong>.</p>
    </div>

    @forelse ($alternativas as $i => $alt)
        <div class="cd-card space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="font-extrabold text-lg">{{ $i + 1 }}. {{ $alt['receita']->favorita ? '⭐ ' : '' }}{{ $alt['receita']->nome }}
                    <span class="cd-chip">{{ number_format($alt['score'], 0) }} pts</span></div>
                <form method="POST" action="{{ route('cardapio.planejamento.escolher', [$plano, $refeicao]) }}">@csrf
                    <input type="hidden" name="receita_id" value="{{ $alt['receita']->id }}">
                    <button class="cd-btn" type="submit">Usar esta</button>
                </form>
            </div>
            <ul class="list-disc pl-5 text-sm">
                @foreach ($alt['motivos'] as $motivo) <li>{{ $motivo }}</li> @endforeach
                @foreach ($alt['avisos'] as $aviso) <li class="text-amber-800">{{ $aviso }}</li> @endforeach
            </ul>
        </div>
    @empty
        <div class="cd-card">Nenhuma outra receita compatível com esta refeição e com as restrições de quem vai comer.
            Cadastre mais receitas (marcando para qual refeição servem) ou revise as restrições.</div>
    @endforelse
</x-Cardapio::layout>
