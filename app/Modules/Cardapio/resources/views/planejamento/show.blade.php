@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
@php($diasAbrev = \App\Modules\Cardapio\Support\TipoRefeicao::dias())
<x-Cardapio::layout titulo="Semana">
    <div>
        <a class="text-sm underline" href="{{ route('cardapio.planejamento.index') }}">← Planejamento</a>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl md:text-2xl font-extrabold">Semana de {{ $plano->inicio_semana->format('d/m') }} a {{ $plano->fimSemana()->format('d/m') }}</h1>
            <div class="flex flex-wrap gap-2 cd-acoes">
                <form method="POST" action="{{ route('cardapio.planejamento.gerar', $plano) }}">@csrf
                    <button class="cd-btn" type="submit">✨ Gerar cardápio</button>
                </form>
                <form method="POST" action="{{ route('cardapio.planejamento.gerar', $plano) }}"
                      onsubmit="return confirm('Refazer todas as refeições que ainda não foram feitas, inclusive as que você escolheu?')">@csrf
                    <input type="hidden" name="tudo" value="1">
                    <button class="cd-btn cd-btn-sec" type="submit">Refazer tudo</button>
                </form>
                <form method="POST" action="{{ route('cardapio.planejamento.otimizar', $plano) }}">@csrf
                    <button class="cd-btn cd-btn-sec" type="submit" title="Procura trocas que melhorem a semana como um todo">Otimizar semana</button>
                </form>
                <form method="POST" action="{{ route('cardapio.planejamento.sincronizar', $plano) }}"
                      onsubmit="return confirm('Refazer a grade pela rotina, ausências e eventos? Os participantes que você ajustou à mão voltam ao padrão.')">@csrf
                    <button class="cd-btn cd-btn-sec" type="submit">Sincronizar com a rotina</button>
                </form>
                <form method="POST" action="{{ route('cardapio.planejamento.compras', $plano) }}">@csrf
                    <button class="cd-btn" type="submit">🛒 Calcular compras</button>
                </form>
                @if ($lista)
                    <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.compras.show', $lista) }}">Ver lista</a>
                @endif
            </div>
        </div>
    </div>

    @if ($pontuacao > 0)
        <div class="text-sm">Pontuação da semana: <strong>{{ number_format($pontuacao, 0) }}</strong> <span class="opacity-70">(soma das refeições; quanto maior, melhor o aproveitamento de estoque, validade, gostos e variedade)</span></div>
    @endif

    @if ($dias->isEmpty())
        <div class="cd-card">Nenhuma refeição nesta semana. Configure a <a class="underline font-bold" href="{{ route('cardapio.rotina.index') }}">rotina</a> (quem come cada refeição) e crie a semana de novo.</div>
    @else
        <form method="POST" action="{{ route('cardapio.planejamento.salvar', $plano) }}" class="space-y-4" id="form-plano">
            @csrf
            @foreach ($dias as $data => $refeicoes)
                @php($d = \Illuminate\Support\Carbon::parse($data))
                <section class="cd-card space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="font-extrabold text-lg">{{ $diasAbrev[$d->dayOfWeekIso] }}, {{ $d->format('d/m') }}</h2>
                        <button type="submit" class="text-sm underline" formaction="{{ route('cardapio.planejamento.gerar.dia', [$plano, $data]) }}"
                                onclick="return confirm('Refazer as refeições deste dia que ainda não foram feitas?')">refazer o dia</button>
                    </div>
                    @foreach ($refeicoes as $refeicao)
                        <div class="border-t border-green-100 pt-3 first:border-0 first:pt-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="cd-linha-rotulo">{{ $rotulos[$refeicao->tipo_refeicao] }}</span>
                                @if ($refeicao->consumida())
                                    <span class="cd-chip">✔ Feita</span>
                                    <span>{{ $refeicao->receita?->nome }}</span>
                                    <a class="text-sm underline" href="{{ route('cardapio.historico.index') }}">feedback</a>
                                    <a class="text-sm underline" href="{{ route('cardapio.sobras.index', ['refeicao' => $refeicao->id]) }}">sobrou?</a>
                                @else
                                    <select class="cd-input flex-1 cd-fluido" style="min-width:12rem" name="receitas[{{ $refeicao->id }}]">
                                        <option value="">— escolher receita —</option>
                                        @php($compativeis = $receitas->filter(fn ($r) => in_array($refeicao->tipo_refeicao, $r->refeicoes_compativeis ?? [], true)))
                                        @php($outras = $receitas->diff($compativeis))
                                        @if ($compativeis->isNotEmpty())
                                            <optgroup label="Combina com {{ strtolower($rotulos[$refeicao->tipo_refeicao]) }}">
                                                @foreach ($compativeis as $r) <option value="{{ $r->id }}" @selected($refeicao->receita_id === $r->id)>{{ $r->favorita ? '⭐ ' : '' }}{{ $r->nome }}</option> @endforeach
                                            </optgroup>
                                        @endif
                                        @if ($outras->isNotEmpty())
                                            <optgroup label="Outras">
                                                @foreach ($outras as $r) <option value="{{ $r->id }}" @selected($refeicao->receita_id === $r->id)>{{ $r->favorita ? '⭐ ' : '' }}{{ $r->nome }}</option> @endforeach
                                            </optgroup>
                                        @endif
                                    </select>
                                    <a class="cd-btn cd-btn-sec" href="{{ route('cardapio.planejamento.alternativas', [$plano, $refeicao]) }}">Trocar</a>
                                    <button type="submit" class="cd-btn cd-btn-sec" formaction="{{ route('cardapio.planejamento.gerar.refeicao', [$plano, $refeicao]) }}" title="Sugerir outra receita">↻</button>
                                    @if ($refeicao->receita_id)
                                        <button type="submit" class="cd-btn cd-btn-sec" formaction="{{ route('cardapio.planejamento.consumir', [$plano, $refeicao]) }}"
                                                onclick="return confirm('Marcar como feita e baixar os ingredientes do estoque? (Salve antes se mudou a receita.)')">Feita</button>
                                    @endif
                                @endif
                            </div>
                            @if ($refeicao->tipo_refeicao === 'lancheira')
                                @php($candidatosItens = $receitas->filter(fn ($r) => in_array('lancheira', $r->refeicoes_compativeis ?? [], true) && $r->id !== $refeicao->receita_id))
                                @if ($refeicao->consumida())
                                    <div class="text-sm mt-1">Itens: {{ $refeicao->itens->map(fn ($i) => $i->receita?->nome)->filter()->join(', ') ?: '—' }}</div>
                                @else
                                    <details class="text-sm mt-1" @if ($refeicao->itens->isNotEmpty()) open @endif>
                                        <summary class="cursor-pointer font-semibold text-green-800">Itens da lancheira ({{ $refeicao->itens->count() + ($refeicao->receita_id ? 1 : 0) }})</summary>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                                            <input type="hidden" name="itens[{{ $refeicao->id }}][]" value="0">
                                            @forelse ($candidatosItens as $r)
                                                <label class="flex items-center gap-1">
                                                    <input type="checkbox" name="itens[{{ $refeicao->id }}][]" value="{{ $r->id }}" @checked($refeicao->itens->contains('receita_id', $r->id))> {{ $r->nome }}
                                                </label>
                                            @empty
                                                <span>Marque receitas como “Lancheira escolar” para escolher os itens.</span>
                                            @endforelse
                                        </div>
                                    </details>
                                @endif
                            @endif
                            @if ($refeicao->justificativa)
                                <details class="text-sm mt-1">
                                    <summary class="cursor-pointer font-semibold text-green-800">Por que esta receita?</summary>
                                    <ul class="list-disc pl-5 mt-1">
                                        @foreach (explode("\n", $refeicao->justificativa) as $linha)
                                            <li class="{{ str_starts_with($linha, '⚠') ? 'text-amber-800' : '' }}">{{ ltrim($linha, '⚠ ') }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                            <details class="text-sm mt-1">
                                <summary class="cursor-pointer">{{ $refeicao->pessoas->pluck('nome')->join(', ') ?: 'Ninguém' }} ({{ $refeicao->qtd_pessoas }})</summary>
                                @unless ($refeicao->consumida())
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                                        <input type="hidden" name="pessoas[{{ $refeicao->id }}][]" value="0">
                                        @foreach ($pessoas as $pessoa)
                                            <label class="flex items-center gap-1">
                                                <input type="checkbox" name="pessoas[{{ $refeicao->id }}][]" value="{{ $pessoa->id }}" @checked($refeicao->pessoas->contains('id', $pessoa->id))>
                                                {{ $pessoa->nome }}
                                            </label>
                                        @endforeach
                                    </div>
                                @endunless
                            </details>
                        </div>
                    @endforeach
                </section>
            @endforeach

            <button class="cd-btn" type="submit">Salvar cardápio</button>
        </form>
    @endif

    <form method="POST" action="{{ route('cardapio.planejamento.destroy', $plano) }}" onsubmit="return confirm('Remover esta semana?')">
        @csrf @method('DELETE')
        <button class="cd-btn cd-btn-perigo" type="submit">Remover semana</button>
    </form>
</x-Cardapio::layout>
