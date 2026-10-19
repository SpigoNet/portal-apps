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

            @include('Pidgey::agendamentos._form', ['agendamento' => new \App\Modules\Pidgey\Models\Agendamento])
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
                                    @if ($a->enviar_imagem)
                                        <span class="text-xs text-amber-300" title="Envia imagem do personagem">
                                            <i class="fa-solid fa-image"></i>
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
                                <a href="{{ route('pidgey.agendamentos.edit', $a) }}"
                                    class="rounded-lg border border-amber-500/30 px-3 py-1.5 text-xs font-bold text-amber-300 hover:bg-amber-500/10 transition">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>
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
