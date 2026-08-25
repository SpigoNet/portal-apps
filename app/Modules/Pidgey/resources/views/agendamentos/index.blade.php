@extends('Pidgey::components.layout')

@section('content')
    <div class="space-y-8">
        <header class="border-b border-white/10 pb-4">
            <h1 class="text-2xl font-black tracking-tight text-amber-400">
                <i class="fa-solid fa-dove mr-2"></i>Pidgey — Mensageiro
            </h1>
            <p class="text-sm text-slate-400 mt-1">Agende mensagens para suas personas dispararem no Telegram.</p>
        </header>

        {{-- Formulário --}}
        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <h2 class="text-lg font-bold mb-4 text-slate-200">Novo agendamento</h2>

            <form action="{{ route('pidgey.agendamentos.store') }}" method="POST" class="space-y-4" x-data="{
                frequencia: 'una_vez',
                interpretar: true,
                canal: 'telegram',
                dias: [1,2,3,4,5],
            }">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Persona</label>
                        <select name="persona_slug" required
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                            @foreach ($personas as $p)
                                <option value="{{ $p->slug }}">{{ $p->slug }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Canal</label>
                        <select name="canal" x-model="canal"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                            <option value="telegram">Telegram</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">E-mail</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Mensagem</label>
                    <textarea name="mensagem" rows="3" required
                        class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400"
                        placeholder="Escreva a mensagem que a persona deve enviar..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Frequência</label>
                        <select name="frequencia" x-model="frequencia"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                            <option value="una_vez">Uma vez</option>
                            <option value="diario">Diário (1x/dia)</option>
                            <option value="semanal">Semanal</option>
                            <option value="intervalo">Intervalo (a cada N min)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Horário</label>
                        <input type="time" name="hora" value="09:00"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400"
                            x-bind:required="['una_vez','diario','semanal'].includes(frequencia)" />
                    </div>

                    <div x-show="frequencia === 'semanal'">
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Dia da semana</label>
                        <select name="dia_semana"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                            @foreach (['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $i => $d)
                                <option value="{{ $i }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Intervalo --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-show="frequencia === 'intervalo'">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">A cada (minutos)</label>
                        <input type="number" name="intervalo_minutos" value="120" min="10" max="1440"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
                        <small class="text-[11px] text-slate-500">Ex: 120 = de 2 em 2 horas</small>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Horário início</label>
                        <input type="time" name="hora_inicio" value="08:00"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Horário fim</label>
                        <input type="time" name="hora_fim" value="22:00"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
                    </div>
                </div>

                {{-- Dias da semana (diario/intervalo) --}}
                <div x-show="['diario','intervalo'].includes(frequencia)" class="rounded-lg border border-white/10 bg-slate-900/40 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400">Dias</label>
                        <div class="flex gap-2">
                            <button type="button" @click="dias = [1,2,3,4,5]"
                                class="rounded bg-slate-700 px-2 py-1 text-[11px] font-bold text-slate-200 hover:bg-slate-600">Dias úteis</button>
                            <button type="button" @click="dias = [0,1,2,3,4,5,6]"
                                class="rounded bg-slate-700 px-2 py-1 text-[11px] font-bold text-slate-200 hover:bg-slate-600">Todos</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @foreach (['Dom' => 0,'Seg' => 1,'Ter' => 2,'Qua' => 3,'Qui' => 4,'Sex' => 5,'Sáb' => 6] as $nome => $valor)
                            <label class="flex items-center gap-1 text-sm text-slate-300">
                                <input type="checkbox" name="dias_semana[]" value="{{ $valor }}" x-model="dias"
                                    class="rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400" />
                                {{ $nome }}
                            </label>
                        @endforeach
                    </div>
                    <small class="text-[11px] text-slate-500">Deixe marcado "Dias úteis" para enviar só de Seg a Sex.</small>
                </div>

                {{-- Período (data início / fim) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="['diario','semanal','intervalo'].includes(frequencia)">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Data início <span class="text-slate-500 normal-case">(opcional)</span></label>
                        <input type="date" name="data_inicio"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
                        <small class="text-[11px] text-slate-500">Se vazio, começa imediatamente.</small>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Data fim <span class="text-slate-500 normal-case">(opcional)</span></label>
                        <input type="date" name="data_fim"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
                        <small class="text-[11px] text-slate-500">Ex: remédio por 5 dias — após essa data o agendamento é encerrado.</small>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="interpretar" value="1" x-model="interpretar"
                        class="rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400" />
                    Reescrever no estilo da persona (IA)
                </label>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Modelo de IA</label>
                    <select name="ai_model_id"
                        class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                        <option value="">Padrão do sistema</option>
                        @foreach ($modelos as $m)
                            <option value="{{ $m->id }}">{{ $m->nome }} ({{ $m->provedor->nome ?? '—' }})</option>
                        @endforeach
                    </select>
                    <small class="text-[11px] text-slate-500">Modelo usado para reescrever a mensagem. Se vazio, usa o padrão do portal.</small>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400">Conteúdos dinâmicos <span class="text-slate-500 normal-case">(opcional)</span></label>
                        <a href="{{ route('pidgey.conteudos.index') }}" class="text-[11px] font-bold text-amber-400 hover:underline">
                            Gerenciar
                        </a>
                    </div>
                    <select name="conteudos_dinamico[]" multiple
                        class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                    @foreach ($conteudos as $c)
                        <option value="{{ $c->id }}">{{ $c->nome }} — {{ $c->tipo_label }}{{ $c->sistema ? ' (Sistema)' : '' }}</option>
                    @endforeach
                    </select>
                    <small class="text-[11px] text-slate-500">São adicionados ao contexto da IA ao gerar a mensagem.</small>
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-black uppercase tracking-widest text-slate-950 hover:bg-amber-400 transition">
                        <i class="fa-solid fa-paper-plane"></i> Agendar
                    </button>
                </div>
            </form>
        </section>

        {{-- Lista --}}
        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <h2 class="text-lg font-bold mb-4 text-slate-200">Agendamentos</h2>

            @if ($grupos->isEmpty())
                <p class="text-sm text-slate-500">Nenhum agendamento ainda.</p>
            @else
                <div class="space-y-8">
                    @foreach ($grupos as $slug => $grupo)
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                @if (! empty($fotos[$slug]))
                                    <img src="{{ $fotos[$slug] }}" alt="{{ $slug }}"
                                        class="h-10 w-10 rounded-full object-cover border border-white/10" />
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/20 text-amber-300 font-black uppercase">
                                        {{ substr($slug, 0, 1) }}
                                    </span>
                                @endif
                                <h3 class="text-base font-bold text-slate-100">{{ $slug }}</h3>
                                <span class="text-xs text-slate-500">{{ $grupo->count() }} agendamento(s)</span>
                            </div>

                            <div class="space-y-3">
                                @foreach ($grupo as $a)
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 rounded-xl border border-white/10 bg-slate-900/50 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">{{ $a->canal }}</span>
                                    @if ($a->interpretar)
                                        <span class="text-xs text-indigo-400" title="Reescrita por IA">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        </span>
                                    @endif
                                    @foreach ($a->conteudosDinamicos as $c)
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-sky-300/80" title="{{ $c->tipo_label }}">
                                            <i class="fa-solid fa-layer-group"></i> {{ $c->nome }}
                                        </span>
                                    @endforeach
                                    @if ($a->ai_model_id)
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-fuchsia-300/80" title="Modelo de IA">
                                            <i class="fa-solid fa-microchip"></i> {{ $a->aiModel->nome ?? '—' }}
                                        </span>
                                    @else
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-fuchsia-300/50" title="Modelo padrão do sistema">
                                            <i class="fa-solid fa-microchip"></i> padrão
                                        </span>
                                    @endif
                                    <span class="text-xs font-semibold {{ $a->ativo ? 'text-emerald-400' : 'text-slate-500' }}">
                                        {{ $a->ativo ? 'Ativo' : 'Pausado' }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm text-slate-200">{{ $a->mensagem }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $a->frequencia_label }}
                                    @if ($a->proxima_execucao)
                                        · próximo: {{ $a->proxima_execucao->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <form action="{{ route('pidgey.agendamentos.enviar-agora', $a) }}" method="POST"
                                    onsubmit="return confirm('Enviar esta mensagem agora?');">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-lg border border-emerald-500/30 px-3 py-1.5 text-xs font-bold text-emerald-400 hover:bg-emerald-500/10 transition">
                                        <i class="fa-solid fa-paper-plane"></i> Enviar agora
                                    </button>
                                </form>
                                <form action="{{ route('pidgey.agendamentos.toggle', $a) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-bold text-slate-300 hover:bg-white/10 transition">
                                        {{ $a->ativo ? 'Pausar' : 'Ativar' }}
                                    </button>
                                </form>
                                <form action="{{ route('pidgey.agendamentos.destroy', $a) }}" method="POST"
                                    onsubmit="return confirm('Remover este agendamento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg border border-red-500/30 px-3 py-1.5 text-xs font-bold text-red-400 hover:bg-red-500/10 transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
