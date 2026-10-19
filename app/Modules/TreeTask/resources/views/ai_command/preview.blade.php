<x-TreeTask::layout titulo="Confirmação da IA" ativo="ia">
    <header>
        <div class="tt-eyebrow">Passo 2 de 2 · Revise antes de aplicar</div>
        <h1 class="tt-h1">Confirme a ação</h1>
    </header>

    <div style="max-width:820px;display:flex;flex-direction:column;gap:16px">
        @isset($prompt)
            <div class="tt-card" style="padding:18px 22px;display:flex;gap:14px;align-items:flex-start">
                <div class="tt-arquivo-ic" style="border-radius:50%"><svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg></div>
                <div style="min-width:0">
                    <div class="tt-sec-tit">Você pediu</div>
                    <div style="font-size:16px;font-weight:500;margin-top:2px;overflow-wrap:anywhere">{{ $prompt }}</div>
                </div>
            </div>
        @endisset

        <section class="tt-card pad" aria-label="O que será feito">
            <h2 style="font-size:20px;font-weight:700;letter-spacing:-.01em;margin-bottom:14px">O que será feito</h2>
            <div class="tt-preview tt-prosa">
                @if(function_exists('clean')) {!! clean($html_preview) !!} @else {!! $html_preview !!} @endif
            </div>
        </section>

        <details class="tt-card" style="padding:4px 22px">
            <summary style="list-style:none;cursor:pointer;display:flex;align-items:center;gap:10px;min-height:48px;font-weight:700">
                <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m8 8-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/></svg>Ver comando gerado (SQL)
                <svg class="tt-i s" viewBox="0 0 24 24" aria-hidden="true" style="margin-left:auto;color:var(--tt-muted)"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <pre class="tt-sql" style="margin:4px 0 18px">{{ $sql_command }}</pre>
        </details>

        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center">
            <a class="tt-btn" href="{{ route('treetask.ai.index') }}">Cancelar e tentar de novo</a>

            <form action="{{ route('treetask.ai.execute') }}" method="POST">
                @csrf
                <input type="hidden" name="sql_command" value="{{ $sql_command }}">
                <button type="submit" class="tt-btn pri" style="min-height:52px">
                    <svg class="tt-i" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>Confirmar e executar
                </button>
            </form>
        </div>
    </div>
</x-TreeTask::layout>
