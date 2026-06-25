@extends('Alfred::layouts.app')

@section('title', 'Cuidando de Você - Alfred')

@section('content')
<div class="text-center" style="padding: 20px;">
    <div style="font-size: 64px; margin-bottom: 20px;">🫂</div>
    <h2 style="color: var(--text-primary); margin-bottom: 24px;">
        Cuidar de você é a prioridade hoje
    </h2>
    
    <p style="font-size: 1.25rem; color: var(--text-muted); margin-bottom: 32px; font-style: italic;">
        "{{ $mensagem }}"
    </p>

    <div style="display: grid; gap: 16px; max-width: 400px; margin: 0 auto;">
        <button onclick="exercicioRespiracao()" class="btn" style="background: var(--accent-blue);">
            🫁 Respirar
            <div style="font-size: 0.75rem; opacity: 0.9;">Exercício 4-7-8</div>
        </button>

        <form action="{{ route('alfred.hidratacao.registrar-padrao') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success" style="width: 100%;">
                💧 Beber Água
                <div style="font-size: 0.75rem; opacity: 0.9;">+250ml</div>
            </button>
        </form>

        <button onclick="alert('Timer de 2 horas iniciado! Descanse 🛌')" class="btn btn-purple">
            🛌 Deitar/Descansar
            <div style="font-size: 0.75rem; opacity: 0.9;">2 horas sem interrupções</div>
        </button>
    </div>

    <form action="{{ route('alfred.dia-ruim.desativar') }}" method="POST" style="margin-top: 32px;">
        @csrf
        <button type="submit" class="btn btn-secondary">
            Estou melhor, voltar ao normal
        </button>
    </form>
</div>

<script>
    function exercicioRespiracao() {
        alert('Exercício de Respiração 4-7-8:\n\n' +
              '1. Inspire pelo nariz contando até 4\n' +
              '2. Segure a respiração contando até 7\n' +
              '3. Expire pela boca contando até 8\n\n' +
              'Repita 4 vezes. Você consegue! 🫁');
    }
</script>
@endsection
