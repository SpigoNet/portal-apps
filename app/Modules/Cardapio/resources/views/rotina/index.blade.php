@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
@php($dias = \App\Modules\Cardapio\Support\TipoRefeicao::dias())
<x-Cardapio::layout titulo="Rotina">
    <h1 class="text-2xl font-extrabold">Rotina alimentar</h1>

    <form method="POST" action="{{ route('cardapio.rotina.refeicoes') }}" class="cd-card space-y-3">
        @csrf
        <div class="font-bold">Refeições planejadas</div>
        <div class="flex flex-wrap gap-x-5 gap-y-2">
            @foreach ($rotulos as $tipo => $rotulo)
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="refeicoes[]" value="{{ $tipo }}" @checked(in_array($tipo, $casa->refeicoes(), true))>
                    {{ $rotulo }}
                </label>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <label class="cd-label mb-0" for="lancheira_itens">Itens por lancheira</label>
            <input class="cd-input" style="width:5rem" id="lancheira_itens" type="number" min="1" max="6" name="lancheira_itens" value="{{ $casa->lancheira_itens }}">
            <span class="text-xs">(ex.: fruta + sanduíche + bebida = 3; o planejador monta sem repetir itens em dias seguidos)</span>
        </div>
        <button class="cd-btn cd-btn-sec" type="submit">Atualizar refeições</button>
    </form>

    <form method="POST" action="{{ route('cardapio.rotina.salvar') }}" class="space-y-4">
        @csrf
        <p class="text-sm">Marque quem participa de cada refeição em cada dia da semana.</p>

        @foreach ($casa->refeicoes() as $tipo)
            <div class="cd-card" x-data>
                <div class="font-extrabold mb-2">{{ $rotulos[$tipo] }}</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" style="display: table">
                        <thead>
                            <tr>
                                <th class="text-left py-1 pr-3">Pessoa</th>
                                @foreach ($dias as $abrev)
                                    <th class="px-2 py-1">{{ $abrev }}</th>
                                @endforeach
                                <th class="px-2 py-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pessoas as $pessoa)
                                <tr class="border-t border-green-100" x-data>
                                    <td class="py-1.5 pr-3 font-semibold whitespace-nowrap">{{ $pessoa->nome }}</td>
                                    @foreach ($dias as $n => $abrev)
                                        <td class="text-center">
                                            <input type="checkbox" class="w-5 h-5"
                                                   name="rotina[{{ $tipo }}][{{ $pessoa->id }}][]" value="{{ $n }}"
                                                   @checked(isset($marcadas["{$tipo}|{$pessoa->id}|{$n}"]))>
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <button type="button" class="text-xs underline"
                                                @click="const c = $el.closest('tr').querySelectorAll('input[type=checkbox]'); const t = [...c].some(i => !i.checked); c.forEach(i => i.checked = t)">todos</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <button class="cd-btn" type="submit">Salvar rotina</button>
    </form>
</x-Cardapio::layout>
