<x-Cardapio::layout titulo="Pessoas">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold">Pessoas da casa</h1>
        <a class="cd-btn" href="{{ route('cardapio.pessoas.create') }}">+ Nova pessoa</a>
    </div>

    <div class="space-y-3">
        @foreach ($pessoas as $pessoa)
            <div class="cd-card flex flex-wrap items-center justify-between gap-3 {{ $pessoa->ativo ? '' : 'opacity-60' }}">
                <div>
                    <div class="font-extrabold text-lg">{{ $pessoa->nome }}</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <span class="cd-chip">{{ $pessoa->tipo === 'crianca' ? 'Criança' : 'Adulto' }}@if ($pessoa->idade() !== null) · {{ $pessoa->idade() }} anos @endif</span>
                        @if ($pessoa->temLogin())
                            <span class="cd-chip">Login: {{ $pessoa->usuario->email }}</span>
                        @else
                            <span class="cd-chip cd-chip-aviso">Sem login</span>
                        @endif
                        @unless ($pessoa->ativo)
                            <span class="cd-chip cd-chip-aviso">Inativa</span>
                        @endunless
                    </div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.perfil.show', $pessoa) }}">Gostos e restrições</a>
                    <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.pessoas.edit', $pessoa) }}">Editar</a>
                </div>
            </div>
        @endforeach
    </div>
</x-Cardapio::layout>
