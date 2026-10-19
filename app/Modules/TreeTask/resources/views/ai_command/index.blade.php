@php
    $exemplo = match ($type ?? null) {
        'project' => 'Adicione uma fase “Revisão” e mova todas as tarefas concluídas para ela.',
        'task' => 'Mude a prioridade para Urgente e adicione na descrição que preciso ligar antes.',
        default => 'Crie um projeto novo...',
    };

    $sugestoes = match ($type ?? null) {
        'project' => ['Adicione uma fase “Revisão” e mova as tarefas concluídas para ela', 'Marque como urgente o que vence esta semana', 'Crie 5 tarefas para a última fase'],
        'phase' => ['Crie 5 tarefas para esta fase', 'Mude a prioridade de todas as tarefas para Alta'],
        'task' => ['Mude a prioridade para Urgente', 'Adicione na descrição que preciso ligar antes'],
        default => [],
    };
@endphp
<x-TreeTask::layout titulo="Assistente IA" ativo="ia">
    <header>
        <div class="tt-eyebrow">Peça em linguagem natural</div>
        <h1 class="tt-h1">Assistente IA</h1>
    </header>

    <div class="tt-ia">
        <div class="tt-ia-corpo" x-data="{ texto: '' }">
            @isset($contextLabel)
                <div class="tt-contexto">
                    <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
                    <span style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase">Contexto ativo</span>
                    <span style="font-weight:700;font-size:15px">{{ $contextLabel }}</span>
                </div>
            @endisset

            @if($errors->any())
                <div class="tt-alert erro" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="tt-card pad" style="display:flex;flex-direction:column;gap:18px" action="{{ route('treetask.ai.preview') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="id" value="{{ $id }}">

                <label for="prompt" style="font-size:20px;font-weight:700;letter-spacing:-.01em;font-family:'Montserrat',sans-serif">O que você deseja fazer?</label>
                <textarea id="prompt" name="prompt" class="tt-prompt" rows="5" x-model="texto" placeholder="Ex.: {{ $exemplo }}" required autofocus></textarea>

                @if($sugestoes !== [])
                    <div style="display:flex;flex-wrap:wrap;gap:10px;padding-top:16px;border-top:1px solid var(--tt-line-2)">
                        @foreach($sugestoes as $sugestao)
                            <button type="button" class="tt-sug" @click="texto = {{ \Illuminate\Support\Js::from($sugestao) }}">
                                <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--tt-brand)"><path d="M12 5v14M5 12h14"/></svg>{{ $sugestao }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center">
                    <a href="{{ url()->previous() }}" class="tt-btn ghost">Voltar</a>
                    <button type="submit" class="tt-btn pri">
                        <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg>Gerar ação
                    </button>
                </div>
            </form>
        </div>

        <aside class="tt-card tt-ia-lado">
            <div style="font-weight:700;font-size:16px">Como funciona</div>
            @foreach([['1', 'Descreva', 'Escreva o que quer mudar, com suas palavras.'], ['2', 'Revise', 'Veja em linguagem simples o que será feito.'], ['3', 'Confirme', 'Só então a alteração é aplicada.']] as [$n, $titulo, $texto])
                <div class="tt-passo">
                    <b>{{ $n }}</b>
                    <div><div style="font-weight:700">{{ $titulo }}</div><div class="tt-muted">{{ $texto }}</div></div>
                </div>
            @endforeach
            <div class="tt-alert ok">Nada é alterado até você confirmar.</div>
        </aside>
    </div>
</x-TreeTask::layout>
