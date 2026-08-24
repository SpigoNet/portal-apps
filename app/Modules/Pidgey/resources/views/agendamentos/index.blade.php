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
                            <option value="diario">Diário</option>
                            <option value="semanal">Semanal</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Horário</label>
                        <input type="time" name="hora" value="09:00"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
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

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="interpretar" value="1" x-model="interpretar"
                        class="rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400" />
                    Reescrever no estilo da persona (IA)
                </label>

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

            @if ($agendamentos->isEmpty())
                <p class="text-sm text-slate-500">Nenhum agendamento ainda.</p>
            @else
                <div class="space-y-3">
                    @foreach ($agendamentos as $a)
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 rounded-xl border border-white/10 bg-slate-900/50 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-amber-300">
                                        {{ $a->persona_slug }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $a->canal }}</span>
                                    @if ($a->interpretar)
                                        <span class="text-xs text-indigo-400" title="Reescrita por IA">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i>
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
            @endif
        </section>
    </div>
@endsection
