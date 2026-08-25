@extends('Pidgey::components.layout')

@section('content')
    <div class="space-y-8">
        <header class="border-b border-white/10 pb-4">
            <h1 class="text-2xl font-black tracking-tight text-amber-400">
                <i class="fa-solid fa-layer-group mr-2"></i>Conteúdos Dinâmicos
            </h1>
            <p class="text-sm text-slate-400 mt-1">Textos e fontes de dados que podem ser injetados no system prompt da persona ao enviar um agendamento.</p>
        </header>

        {{-- Formulário --}}
        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <h2 class="text-lg font-bold mb-4 text-slate-200">Novo conteúdo</h2>

            <form action="{{ route('pidgey.conteudos.store') }}" method="POST" class="space-y-4" x-data="{ tipo: 'texto' }">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Nome</label>
                        <input type="text" name="nome" required
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400"
                            placeholder="Ex.: Relatório Financeiro (Nami)" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Tipo</label>
                        <select name="tipo" x-model="tipo"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                            <option value="texto">Texto pré-pronto</option>
                        </select>
                        <small class="text-[11px] text-slate-500">Fontes de sistema (ex.: Relatório Financeiro do Mithril) já vêm disponíveis automaticamente.</small>
                    </div>
                </div>

                <div x-show="tipo === 'texto'">
                    <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Conteúdo</label>
                    <textarea name="conteudo" rows="4"
                        class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400"
                        placeholder="Texto que será adicionado ao contexto da persona..."></textarea>
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-black uppercase tracking-widest text-slate-950 hover:bg-amber-400 transition">
                        <i class="fa-solid fa-plus"></i> Criar conteúdo
                    </button>
                </div>
            </form>
        </section>

        {{-- Lista --}}
        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <h2 class="text-lg font-bold mb-4 text-slate-200">Conteúdos</h2>

            @if ($conteudos->isEmpty())
                <p class="text-sm text-slate-500">Nenhum conteúdo ainda.</p>
            @else
                <div class="space-y-3">
                    @foreach ($conteudos as $c)
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 rounded-xl border border-white/10 bg-slate-900/50 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-100">{{ $c->nome }}</span>
                                    <span class="inline-flex items-center rounded-full bg-indigo-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-indigo-300">
                                        {{ $c->tipo_label }}
                                    </span>
                                    @if ($c->sistema)
                                        <span class="inline-flex items-center rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-amber-300">
                                            Sistema
                                        </span>
                                    @endif
                                    <span class="text-xs font-semibold {{ $c->ativo ? 'text-emerald-400' : 'text-slate-500' }}">
                                        {{ $c->ativo ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </div>
                                @if ($c->tipo === 'texto' && $c->conteudo)
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $c->conteudo }}</p>
                                @endif
                            </div>

                            @unless ($c->sistema)
                                <form action="{{ route('pidgey.conteudos.destroy', $c) }}" method="POST"
                                    onsubmit="return confirm('Remover este conteúdo?');" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg border border-red-500/30 px-3 py-1.5 text-xs font-bold text-red-400 hover:bg-red-500/10 transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endunless
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
