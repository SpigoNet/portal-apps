@extends('BolaoReuniao::layouts.bolao')

@section('content')
    <div class="max-w-2xl w-full text-center space-y-8">
        <div class="glass p-8 rounded-3xl shadow-2xl space-y-6">
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">
                Admin — Bolão
            </h1>

            @if($activeMeeting)
                <div class="space-y-2">
                    <p class="text-lg font-semibold text-white">{{ $activeMeeting->name }}</p>
                    <p class="text-slate-400">{{ $activeMeeting->guesses->count() }} pessoa(s) participando</p>
                </div>

                <form action="{{ route('bolao.admin.end', $activeMeeting->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-red-500/20">
                        Encerrar Reunião
                    </button>
                </form>
            @else
                <p class="text-slate-400">Nenhuma reunião ativa no momento.</p>
            @endif

            <a href="{{ route('bolao.index') }}"
                class="inline-block text-sm text-slate-500 hover:text-slate-300 transition-colors">
                ← Voltar ao Bolão
            </a>
        </div>
    </div>
@endsection
