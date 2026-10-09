<x-app-layout>
    <x-slot name="header">
        @if($contrato->status !== 'ativo')
            <div
                class="mb-6 rounded-md p-4 {{ $contrato->status == 'cancelado' ? 'bg-red-50 border-l-4 border-red-500' : 'bg-gray-100 border-l-4 border-gray-500' }}">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 {{ $contrato->status == 'cancelado' ? 'text-red-400' : 'text-gray-400' }}"
                             viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium {{ $contrato->status == 'cancelado' ? 'text-red-800' : 'text-gray-800' }} uppercase">
                            Contrato {{ ucfirst($contrato->status) }}
                        </h3>
                        <div
                            class="mt-2 text-sm {{ $contrato->status == 'cancelado' ? 'text-red-700' : 'text-gray-700' }}">
                            <p>Este projeto está arquivado. As informações estão disponíveis apenas para consulta
                                (Leitura).</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @php
                $isLivre = $contrato->tipo === 'livre';
                $totalSeparado = $contrato->apontamentos->where('faturamento_status', 'separado')->count();
                $totalFaturado = $contrato->apontamentos->where('faturamento_status', 'faturado')->count();
            @endphp

            <div
                class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 {{ $isLivre ? 'border-slate-500' : ($contrato->saldo < 0 ? 'border-red-500' : 'border-blue-500') }}">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Cliente</p>
                        <p class="text-lg font-bold text-gray-800">{{ $contrato->cliente->nome }}</p>
                    </div>
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Total Consumido</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($contrato->horas_consumidas, 2, ',', '.') }}
                            h</p>
                    </div>
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Total Contratado</p>
                        <p class="text-2xl font-bold text-gray-700">{{ number_format($contrato->horas_contratadas, 2, ',', '.') }}
                            h</p>
                    </div>
                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-400 uppercase font-bold">Valor Hora</p>
                        <p class="text-2xl font-bold text-emerald-700">R$ {{ number_format($contrato->valor_hora, 2, ',', '.') }}</p>
                    </div>
                    @if($isLivre)
                        <div class="px-4 py-2">
                            <p class="text-xs text-gray-400 uppercase font-bold">Faturamento</p>
                            <p class="text-sm font-bold text-slate-700">
                                {{ $totalSeparado }} separado(s) / {{ $totalFaturado }} faturado(s)
                            </p>
                        </div>
                    @else
                        <div class="px-4 py-2">
                            <p class="text-xs text-gray-400 uppercase font-bold">Saldo Disponível</p>
                            <p class="text-2xl font-bold {{ $contrato->saldo < 0 ? 'text-red-500' : 'text-green-500' }}">
                                {{ number_format($contrato->saldo, 2, ',', '.') }} h
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            @if($isLivre)
                @php
                    $statusMeta = [
                        'nao_separado' => ['label' => 'Não separado', 'class' => 'bg-gray-100 text-gray-700 border-gray-300'],
                        'separado' => ['label' => 'Separado', 'class' => 'bg-yellow-100 text-yellow-800 border-yellow-300'],
                        'aprovado_cliente' => ['label' => 'Aprovado cliente', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
                        'faturado' => ['label' => 'Faturado', 'class' => 'bg-green-100 text-green-800 border-green-300'],
                    ];
                    $apontamentosPorMesCiclo = $contrato->apontamentos
                        ->groupBy(fn ($a) => $a->data_realizacao->format('Y-m'))
                        ->sortKeysDesc();
                    $itensCiclo = $contrato->apontamentos->mapWithKeys(fn ($a) => [
                        $a->id => [
                            'status' => $a->faturamento_status ?? 'nao_separado',
                            'mes' => $a->data_realizacao->format('Y-m'),
                            'data' => $a->data_realizacao->format('Y-m-d'),
                            'horas' => round($a->horas, 2),
                            'texto' => mb_strtolower((string) $a->descricao),
                        ],
                    ]);
                    $mesesCiclo = $apontamentosPorMesCiclo->map(fn ($grupo, $mes) => [
                        'valor' => $mes,
                        'label' => \Carbon\Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F Y'),
                    ])->values();
                    $statusCiclo = collect($statusMeta)->map(fn ($m, $k) => ['valor' => $k, 'label' => $m['label']])->values();
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-slate-500 mt-8"
                     x-data="cicloFaturamento({{ Js::from($itensCiclo) }}, '{{ now()->format('Y-m') }}')">
                    <div class="p-6 border-b border-gray-200 bg-slate-50">
                        <h3 class="text-lg font-bold text-slate-700">Ciclo de Faturamento</h3>
                        <p class="text-sm text-slate-500">Filtre por status ou mês, selecione em lote e avance o status: separar, aprovar e faturar.</p>
                    </div>

                    @can('gh.operacional')
                        <form action="{{ route('gestor-horas.faturamento-status', $contrato->id) }}" method="POST">
                            @csrf
                            <template x-for="id in selecionados" :key="id">
                                <input type="hidden" name="apontamentos[]" :value="id">
                            </template>

                            {{-- Filtros --}}
                            <div class="p-4 border-b border-gray-200 space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="filtroStatus = ''"
                                            :class="filtroStatus === '' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
                                            class="px-3 py-1.5 rounded-full border text-xs font-bold transition">
                                        Todos (<span x-text="contar('')"></span>)
                                    </button>
                                    @foreach($statusMeta as $chave => $meta)
                                        <button type="button" @click="filtroStatus = '{{ $chave }}'"
                                                :class="filtroStatus === '{{ $chave }}' ? 'ring-2 ring-offset-1 ring-slate-700' : ''"
                                                class="px-3 py-1.5 rounded-full border text-xs font-bold transition {{ $meta['class'] }}">
                                            {{ $meta['label'] }} (<span x-text="contar('{{ $chave }}')"></span>)
                                        </button>
                                    @endforeach
                                </div>

                                <div class="flex flex-wrap gap-3 items-center">
                                    <select x-model="filtroMes" class="rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Todos os meses</option>
                                        @foreach($mesesCiclo as $m)
                                            <option value="{{ $m['valor'] }}">{{ ucfirst($m['label']) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex items-center gap-1 text-xs text-gray-500">
                                        <span>De</span>
                                        <input type="date" x-model="dataDe" class="rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                        <span>até</span>
                                        <input type="date" x-model="dataAte" class="rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <input type="text" x-model.debounce.200ms="busca" placeholder="Buscar na descrição..."
                                           class="rounded border-gray-300 text-sm w-64 focus:ring-blue-500 focus:border-blue-500">
                                    <button type="button" x-show="filtroStatus || filtroMes || busca || dataDe || dataAte" x-cloak
                                            @click="filtroStatus = ''; filtroMes = ''; busca = ''; dataDe = ''; dataAte = ''"
                                            class="text-xs text-gray-500 underline">Limpar filtros</button>
                                    <div class="ml-auto flex flex-wrap gap-2">
                                        <button type="button" @click="selecionarVisiveis()"
                                                class="px-3 py-1.5 rounded border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-100">
                                            Selecionar todos filtrados (<span x-text="visiveisSelecionaveis().length"></span>)
                                        </button>
                                        <button type="button" @click="selecionados = []" x-show="selecionados.length" x-cloak
                                                class="px-3 py-1.5 rounded border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-100">
                                            Limpar seleção
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Lista agrupada por mês --}}
                            <div class="overflow-auto max-h-[60vh]">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-white sticky top-0 z-10 shadow-sm">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">
                                            <input type="checkbox" title="Selecionar/desmarcar todos os filtrados"
                                                   :checked="todosVisiveisSelecionados()"
                                                   @change="$event.target.checked ? selecionarVisiveis() : desmarcarVisiveis()"
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </th>
                                        <th class="px-4 py-3 text-left">Data</th>
                                        <th class="px-4 py-3 text-left">Descrição</th>
                                        <th class="px-4 py-3 text-right">Tempo</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @forelse($apontamentosPorMesCiclo as $mes => $grupo)
                                        @php
                                            $mesLabel = \Carbon\Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F Y');
                                        @endphp
                                        <tr class="bg-slate-100" x-show="mesVisivel('{{ $mes }}')">
                                            <td class="px-4 py-2">
                                                <input type="checkbox" title="Selecionar o mês inteiro (conforme filtros)"
                                                       :checked="mesSelecionado('{{ $mes }}')"
                                                       @change="alternarMes('{{ $mes }}', $event.target.checked)"
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td colspan="2" class="px-4 py-2 font-bold text-slate-700">{{ ucfirst($mesLabel) }}</td>
                                            <td class="px-4 py-2 text-right font-mono text-slate-700" x-text="horasMes('{{ $mes }}')"></td>
                                            <td class="px-4 py-2 text-xs text-slate-500" x-text="contarMes('{{ $mes }}') + ' atividade(s)'"></td>
                                        </tr>
                                        @foreach($grupo as $apontamento)
                                            @php
                                                $st = $apontamento->faturamento_status ?? 'nao_separado';
                                                $meta = $statusMeta[$st] ?? $statusMeta['nao_separado'];
                                            @endphp
                                            <tr x-show="visivel({{ $apontamento->id }})"
                                                :class="selecionados.includes({{ $apontamento->id }}) ? 'bg-blue-50' : 'hover:bg-gray-50'">
                                                <td class="px-4 py-3">
                                                    @if($st !== 'faturado')
                                                        <input type="checkbox" value="{{ $apontamento->id }}" x-model.number="selecionados"
                                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $apontamento->data_realizacao->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 text-gray-800 max-w-md">
                                                    <p class="truncate" title="{{ $apontamento->descricao }}">{{ Str::limit(strtok(trim((string) $apontamento->descricao), "\n"), 80) }}</p>
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ number_format($apontamento->horas, 2, ',', '.') }} h</td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex px-2 py-1 rounded border text-xs font-semibold {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 italic">
                                                Nenhum apontamento encontrado para este contrato.
                                            </td>
                                        </tr>
                                    @endforelse
                                    <tr x-show="visiveisIds().length === 0 && {{ $contrato->apontamentos->count() }} > 0" x-cloak>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-400 italic">Nenhum apontamento corresponde aos filtros.</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Barra de ação --}}
                            <div class="p-4 border-t border-gray-200 bg-white flex flex-wrap items-center gap-3 justify-between">
                                <p class="text-sm text-gray-600">
                                    <span class="font-bold" x-text="selecionados.length"></span> selecionado(s)
                                    · <span class="font-mono" x-text="horasSelecionadas()"></span>
                                </p>
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <button type="submit" formaction="{{ route('gestor-horas.export-separados', $contrato->id) }}"
                                            :disabled="selecionados.length === 0"
                                            class="bg-indigo-700 hover:bg-indigo-600 border border-indigo-700 text-white font-bold py-2 px-4 rounded text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                        Exportar Excel (<span x-text="selecionados.length"></span>)
                                    </button>
                                    <button type="submit" name="acao" value="separar" :disabled="elegiveis('nao_separado') === 0"
                                            class="bg-gray-900 hover:bg-gray-800 border border-gray-900 text-white font-bold py-2 px-4 rounded text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                        Separar (<span x-text="elegiveis('nao_separado')"></span>)
                                    </button>
                                    <button type="submit" name="acao" value="aprovar" :disabled="elegiveis('separado') === 0"
                                            class="bg-blue-700 hover:bg-blue-600 border border-blue-700 text-white font-bold py-2 px-4 rounded text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                        Marcar Aprovado (<span x-text="elegiveis('separado')"></span>)
                                    </button>
                                    <button type="submit" name="acao" value="faturar" :disabled="elegiveis('aprovado_cliente') === 0"
                                            class="bg-green-700 hover:bg-green-600 border border-green-700 text-white font-bold py-2 px-4 rounded text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                        Marcar Faturado (<span x-text="elegiveis('aprovado_cliente')"></span>)
                                    </button>
                                </div>
                            </div>
                        </form>


                        @if(session('email_aprovacao_texto'))
                            <div class="p-4 border-t border-gray-200 bg-indigo-50">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-bold text-indigo-800">Corpo do e-mail para aprovação de faturamento</p>
                                    <button type="button"
                                            class="bg-indigo-700 hover:bg-indigo-600 border border-indigo-700 text-white font-bold py-1 px-3 rounded text-xs"
                                            onclick="const campo = document.getElementById('email-aprovacao-faturamento'); campo.select(); document.execCommand('copy');">
                                        Copiar Texto
                                    </button>
                                </div>
                                <textarea id="email-aprovacao-faturamento"
                                          rows="10"
                                          class="w-full rounded border-indigo-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ session('email_aprovacao_texto') }}</textarea>
                            </div>
                        @endif
                    @else
                        <div class="p-6 text-sm text-gray-500">Apenas usuários operacionais podem separar apontamentos para faturamento.</div>
                    @endcan
                </div>

                <style>[x-cloak]{display:none !important}</style>
                <script>
                    function cicloFaturamento(itens, mesAtual) {
                        const fmt = (h) => h.toFixed(2).replace('.', ',') + ' h';
                        return {
                            itens,
                            selecionados: [],
                            filtroStatus: '',
                            filtroMes: Object.values(itens).some((i) => i.mes === mesAtual) ? mesAtual : '',
                            busca: '',
                            dataDe: '',
                            dataAte: '',
                            visivel(id) {
                                const i = this.itens[id];
                                const termo = this.busca.trim().toLowerCase();
                                return (!this.filtroStatus || i.status === this.filtroStatus)
                                    && (!this.filtroMes || i.mes === this.filtroMes)
                                    && (!this.dataDe || i.data >= this.dataDe)
                                    && (!this.dataAte || i.data <= this.dataAte)
                                    && (!termo || i.texto.includes(termo));
                            },
                            visiveisIds() {
                                return Object.keys(this.itens).map(Number).filter((id) => this.visivel(id));
                            },
                            visiveisSelecionaveis(mes = null) {
                                return this.visiveisIds().filter((id) => this.itens[id].status !== 'faturado' && (!mes || this.itens[id].mes === mes));
                            },
                            contar(status) {
                                return Object.values(this.itens).filter((i) => !status || i.status === status).length;
                            },
                            selecionarVisiveis() {
                                this.selecionados = [...new Set([...this.selecionados, ...this.visiveisSelecionaveis()])];
                            },
                            desmarcarVisiveis() {
                                const ids = this.visiveisSelecionaveis();
                                this.selecionados = this.selecionados.filter((id) => !ids.includes(id));
                            },
                            todosVisiveisSelecionados() {
                                const ids = this.visiveisSelecionaveis();
                                return ids.length > 0 && ids.every((id) => this.selecionados.includes(id));
                            },
                            mesVisivel(mes) {
                                return this.visiveisIds().some((id) => this.itens[id].mes === mes);
                            },
                            mesSelecionado(mes) {
                                const ids = this.visiveisSelecionaveis(mes);
                                return ids.length > 0 && ids.every((id) => this.selecionados.includes(id));
                            },
                            alternarMes(mes, marcado) {
                                const ids = this.visiveisSelecionaveis(mes);
                                this.selecionados = marcado
                                    ? [...new Set([...this.selecionados, ...ids])]
                                    : this.selecionados.filter((id) => !ids.includes(id));
                            },
                            contarMes(mes) {
                                return this.visiveisIds().filter((id) => this.itens[id].mes === mes).length;
                            },
                            horasMes(mes) {
                                return fmt(this.visiveisIds().filter((id) => this.itens[id].mes === mes).reduce((s, id) => s + this.itens[id].horas, 0));
                            },
                            horasSelecionadas() {
                                return fmt(this.selecionados.reduce((s, id) => s + this.itens[id].horas, 0));
                            },
                            elegiveis(status) {
                                return this.selecionados.filter((id) => this.itens[id].status === status).length;
                            },
                        };
                    }
                </script>
            @endif

            @can('gh.operacional')
                @if(auth()->user()->can('gh.operacional') && $contrato->status === 'ativo')
                    <div class="bg-gray-800 text-white overflow-hidden shadow-lg sm:rounded-lg mt-8">
                        <div class="p-6">
                            <div class="flex items-center mb-6">
                                <div class="p-2 bg-blue-600 rounded-lg mr-3 shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold">Novo Apontamento</h3>
                                    <p class="text-sm text-gray-400">Registre suas horas trabalhadas aqui.</p>
                                </div>
                                <a href="{{ route('gestor-horas.mobile.timer') }}"
                                              class="ml-auto text-xs font-bold bg-blue-700 hover:bg-blue-600 text-white border border-blue-700 px-3 py-2 rounded">
                                    MODO MOBILE
                                </a>
                            </div>

                            <form action="{{ route('gestor-horas.apontar', $contrato->id) }}" method="POST">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">DATA</label>
                                        <input type="date" name="data_realizacao" value="{{ date('Y-m-d') }}" required
                                               class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    </div>

                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">VINCULAR A ITEM
                                            (OPCIONAL)</label>
                                        <select name="gh_contrato_item_id"
                                                class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="">-- Atividade Geral / Suporte --</option>
                                            @foreach($contrato->itens as $item)
                                                <option
                                                    value="{{ $item->id }}">{{ Str::limit($item->titulo, 40) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">HORAS
                                            (DECIMAL)</label>
                                        <input type="number" step="0.1" name="horas_gastas" required
                                               placeholder="Ex: 1.5"
                                               class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    </div>

                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-bold text-gray-400 mb-1">DESCRIÇÃO</label>
                                        <div class="flex gap-2">
                                            <input type="text" name="descricao" required
                                                   placeholder="O que foi feito..."
                                                   class="w-full rounded bg-gray-700 border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <button type="submit"
                                                    class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded text-sm transition shadow-lg">
                                                SALVAR
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan

        </div>
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</x-app-layout>
