@extends('Pidgey::components.layout')

@section('content')
    <div class="space-y-8">
        <header class="border-b border-white/10 pb-4">
            <a href="{{ route('pidgey.agendamentos.index') }}" class="text-xs font-bold text-slate-400 hover:text-slate-200">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-amber-400">
                <i class="fa-solid fa-pen mr-2"></i>Editar agendamento
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                {{ $agendamento->persona_slug }} · {{ $agendamento->frequencia_label }}
                · {{ $agendamento->ativo ? 'Ativo' : 'Pausado' }}
            </p>
        </header>

        @if ($errors->any())
            <div class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                @foreach ($errors->all() as $erro)
                    <p>{{ $erro }}</p>
                @endforeach
            </div>
        @endif

        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            @include('Pidgey::agendamentos._form')
        </section>
    </div>
@endsection
