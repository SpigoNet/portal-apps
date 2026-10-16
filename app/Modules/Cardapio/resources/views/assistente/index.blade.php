<x-Cardapio::layout titulo="Assistente">
    <div>
        <h1 class="text-2xl font-extrabold">✨ Assistente</h1>
        <p class="text-sm">Diga o que aconteceu ou o que você quer, em português. A IA só entende o pedido: <strong>nada é alterado até você confirmar</strong>, e quem aplica são as regras do sistema.</p>
    </div>

    <form method="POST" action="{{ route('cardapio.assistente.interpretar') }}" class="cd-card space-y-3"
          x-data="{ texto: @js(old('texto', $proposta['texto'] ?? '')), enviando: false }" @submit="enviando = true">
        @csrf
        <textarea class="cd-input" name="texto" rows="3" maxlength="800" x-model="texto" required
                  placeholder="Ex.: Comprei 2 kg de frango e 1 dúzia de ovos"></textarea>
        <div class="flex flex-wrap gap-2">
            @foreach ($exemplos as $exemplo)
                <button type="button" class="cd-chip" style="cursor:pointer" @click="texto = @js($exemplo)">{{ $exemplo }}</button>
            @endforeach
        </div>
        <button class="cd-btn" type="submit" :disabled="enviando" x-text="enviando ? 'Pensando…' : 'Enviar'">Enviar</button>
    </form>

    @if ($resultados)
        <section class="cd-card space-y-2">
            <h2 class="font-extrabold text-lg">Resultado</h2>
            @foreach ($resultados as $r)
                <div class="text-sm">
                    <span class="cd-chip {{ $r['ok'] ? '' : 'cd-chip-aviso' }}">{{ $r['ok'] ? '✔ feito' : '✖ não feito' }}</span>
                    <strong>{{ $r['resumo'] }}</strong> — {{ $r['texto'] }}
                    @if (! empty($r['receita_id']))
                        <a class="underline font-bold" href="{{ route('cardapio.receitas.show', $r['receita_id']) }}">abrir a receita</a>
                    @endif
                    @if (! empty($r['plano_id']))
                        <a class="underline font-bold" href="{{ route('cardapio.planejamento.show', $r['plano_id']) }}">abrir a semana</a>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if ($proposta)
        @if ($proposta['mensagem'])
            <div class="cd-card text-sm">💬 {{ $proposta['mensagem'] }}</div>
        @endif

        @foreach ($proposta['respostas'] as $linhas)
            <section class="cd-card text-sm space-y-1">
                @foreach ($linhas as $linha) <div>{{ $linha }}</div> @endforeach
            </section>
        @endforeach

        @if ($proposta['pendentes'])
            <form method="POST" action="{{ route('cardapio.assistente.aplicar') }}" class="cd-card space-y-3">
                @csrf
                <input type="hidden" name="token" value="{{ $proposta['token'] }}">
                <h2 class="font-extrabold text-lg">Confirmar antes de aplicar</h2>
                <ul class="space-y-2">
                    @foreach ($proposta['pendentes'] as $acao)
                        <li>
                            <label class="flex items-start gap-2">
                                <input type="checkbox" class="mt-1 w-5 h-5" name="acoes[]" value="{{ $acao['id'] }}" checked>
                                <span>
                                    <span class="font-bold">{{ $acao['resumo'] }}</span>
                                    @foreach ($acao['avisos'] ?? [] as $aviso) <span class="block text-xs text-amber-800">⚠ {{ $aviso }}</span> @endforeach
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                <div class="flex flex-wrap gap-2">
                    <button class="cd-btn" type="submit">Aplicar selecionadas</button>
                    <button class="cd-btn cd-btn-sec" type="submit" formaction="{{ route('cardapio.assistente.descartar') }}" formnovalidate>Descartar</button>
                </div>
            </form>
        @endif

        @if ($proposta['rejeitadas'])
            <section class="cd-card text-sm space-y-1" style="border-color:#fcd34d; background:#fffbeb">
                <div class="font-bold">Não consegui aplicar isto:</div>
                @foreach ($proposta['rejeitadas'] as $motivo) <div>• {{ $motivo }}</div> @endforeach
            </section>
        @endif

        @if (! $proposta['pendentes'] && ! $proposta['respostas'] && ! $proposta['rejeitadas'] && ! $proposta['mensagem'])
            <div class="cd-card text-sm">Não identifiquei nenhuma ação nesse pedido. Tente descrever de outro jeito.</div>
        @endif
    @endif
</x-Cardapio::layout>
