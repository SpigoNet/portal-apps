@php($editando = $receita->exists)
@php($rotulos = \App\Modules\Cardapio\Support\TipoRefeicao::rotulos())
@php($unRotulos = \App\Modules\Cardapio\Support\UnidadeConversor::rotulos())
@php($bases = $ingredientes->pluck('unidade_base', 'id'))
@php($gruposUnidade = \App\Modules\Cardapio\Support\UnidadeConversor::porBase())
@php($linhasIniciais = old('ingredientes', $linhas) ?: [['ingrediente_id' => $ingredientes->first()?->id, 'quantidade' => '', 'unidade' => 'g', 'opcional' => false]])
<x-Cardapio::layout :titulo="$editando ? 'Editar receita' : 'Nova receita'">
    <h1 class="text-2xl font-extrabold">{{ $editando ? 'Editar '.$receita->nome : 'Nova receita' }}</h1>

    @if ($ingredientes->isEmpty())
        <div class="cd-card">Cadastre os ingredientes antes em <a class="underline font-bold" href="{{ route('cardapio.ingredientes.create') }}">Ingredientes</a>.</div>
    @else
        <form method="POST" class="space-y-4"
              action="{{ $editando ? route('cardapio.receitas.update', $receita) : route('cardapio.receitas.store') }}"
              x-data="{ bases: @js($bases), grupos: @js($gruposUnidade), rotulos: @js($unRotulos), linhas: @js($linhasIniciais),
                        opcoes(l) { return this.grupos[this.bases[l.ingrediente_id]] ?? []; },
                        ajusta(l) { if (!this.opcoes(l).includes(l.unidade)) l.unidade = this.opcoes(l)[0]; },
                        add() { const l = { ingrediente_id: Object.keys(this.bases)[0], quantidade: '', unidade: 'g', opcional: false }; this.ajusta(l); this.linhas.push(l); } }"
              x-init="linhas.forEach(l => ajusta(l))">
            @csrf
            @if ($editando) @method('PUT') @endif

            <div class="cd-card space-y-4">
                <div>
                    <label class="cd-label" for="nome">Nome</label>
                    <input class="cd-input" id="nome" name="nome" value="{{ old('nome', $receita->nome) }}" required maxlength="160">
                </div>
                <div>
                    <label class="cd-label" for="descricao">Descrição</label>
                    <textarea class="cd-input" id="descricao" name="descricao" rows="2">{{ old('descricao', $receita->descricao) }}</textarea>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div><label class="cd-label">Porções</label><input class="cd-input" type="number" min="1" name="rendimento" value="{{ old('rendimento', $receita->rendimento) }}" required></div>
                    <div><label class="cd-label">Tempo (min)</label><input class="cd-input" type="number" min="1" name="tempo_preparo" value="{{ old('tempo_preparo', $receita->tempo_preparo) }}"></div>
                    <div><label class="cd-label">Dificuldade</label>
                        <select class="cd-input" name="dificuldade">
                            @foreach (\App\Modules\Cardapio\Models\Receita::DIFICULDADES as $v => $r) <option value="{{ $v }}" @selected(old('dificuldade', $receita->dificuldade) === $v)>{{ $r }}</option> @endforeach
                        </select></div>
                    <div><label class="cd-label">Frequência desejada</label>
                        <select class="cd-input" name="frequencia">
                            @foreach (\App\Modules\Cardapio\Models\Receita::FREQUENCIAS as $v => $r) <option value="{{ $v }}" @selected(old('frequencia', $receita->frequencia) === $v)>{{ $r }}</option> @endforeach
                        </select></div>
                </div>
                <div>
                    <div class="cd-label">Refeições compatíveis</div>
                    <div class="flex flex-wrap gap-x-5 gap-y-1">
                        @foreach ($rotulos as $tipo => $rotulo)
                            <label class="flex items-center gap-2"><input type="checkbox" name="refeicoes_compativeis[]" value="{{ $tipo }}" @checked(in_array($tipo, old('refeicoes_compativeis', $receita->refeicoes_compativeis ?? []), true))> {{ $rotulo }}</label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="cd-label" for="categorias">Categorias (separadas por vírgula)</label>
                    <input class="cd-input" id="categorias" name="categorias" value="{{ old('categorias', implode(', ', $receita->categorias ?? [])) }}" placeholder="comida saudável, final de semana">
                </div>
                <label class="flex items-center gap-2 font-semibold"><input type="checkbox" name="favorita" value="1" @checked(old('favorita', $receita->favorita))> ⭐ Favorita da família</label>
            </div>

            <div class="cd-card space-y-3">
                <h2 class="font-extrabold text-lg">Ingredientes</h2>
                <template x-for="(l, i) in linhas" :key="i">
                    <div class="grid grid-cols-12 gap-2 items-center">
                        <select class="cd-input col-span-12 sm:col-span-5" :name="`ingredientes[${i}][ingrediente_id]`" x-model="l.ingrediente_id" @change="ajusta(l)">
                            @foreach ($ingredientes as $ingrediente) <option value="{{ $ingrediente->id }}">{{ $ingrediente->nome }}</option> @endforeach
                        </select>
                        <input class="cd-input col-span-5 sm:col-span-2" type="number" step="any" min="0" :name="`ingredientes[${i}][quantidade]`" x-model="l.quantidade" placeholder="qtd" required>
                        <select class="cd-input col-span-7 sm:col-span-2" :name="`ingredientes[${i}][unidade]`" x-model="l.unidade">
                            <template x-for="o in opcoes(l)" :key="o"><option :value="o" x-text="rotulos[o]" :selected="o === l.unidade"></option></template>
                        </select>
                        <label class="col-span-8 sm:col-span-2 flex items-center gap-1 text-sm">
                            <input type="checkbox" value="1" :name="`ingredientes[${i}][opcional]`" x-model="l.opcional"> opcional
                        </label>
                        <button type="button" class="col-span-4 sm:col-span-1 text-red-700 underline text-sm" @click="linhas.splice(i, 1)" x-show="linhas.length > 1">tirar</button>
                    </div>
                </template>
                <button type="button" class="cd-btn cd-btn-sec" @click="add()">+ Ingrediente</button>
            </div>

            <div class="cd-card">
                <label class="cd-label" for="modo_preparo">Modo de preparo</label>
                <textarea class="cd-input" id="modo_preparo" name="modo_preparo" rows="6">{{ old('modo_preparo', $receita->modo_preparo) }}</textarea>
            </div>

            <div class="flex gap-2">
                <button class="cd-btn" type="submit">Salvar receita</button>
                <a class="cd-btn cd-btn-sec" href="{{ $editando ? route('cardapio.receitas.show', $receita) : route('cardapio.receitas.index') }}">Cancelar</a>
            </div>
        </form>
    @endif
</x-Cardapio::layout>
