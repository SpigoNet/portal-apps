@extends('BolaoReuniao::layouts.bolao')

@section('content')
    <div class="max-w-md w-full glass p-8 rounded-3xl shadow-2xl space-y-6">
        <div class="text-center">
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">Bolão
                Fatec</h1>
            <p class="text-slate-400 mt-2">{{ $meeting->name }}</p>
        </div>

        <form action="{{ route('bolao.guess') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="meeting_id" value="{{ $meeting->id }}">

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Seu Nome</label>
                <input type="text" name="name" required placeholder="Digite seu nome"
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Horário que a reunião acaba</label>
                <input type="time" name="guess" required
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-emerald-500/20">
                Enviar meu Chute
            </button>
        </form>
    </div>
@endsection