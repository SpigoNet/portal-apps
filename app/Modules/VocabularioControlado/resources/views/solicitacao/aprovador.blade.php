{{-- Partial para perfil: aprovador --}}
<div class="mdl-tabs mdl-js-tabs">
    <div class="mdl-tabs__tab-bar" style="justify-content:flex-start;">
        <a href="#aprovar-termos" class="mdl-tabs__tab is-active">Autorizar termos</a>
        <a href="#lista-completa-apr" class="mdl-tabs__tab">Lista Completa</a>
    </div>

    {{-- Aba: Autorizar --}}
    <div class="mdl-tabs__panel is-active" id="aprovar-termos">
        <div class="mdl-grid">
            <div class="mdl-cell mdl-cell--12-col mdl-typography--headline">
                Lista de termos a serem aprovados
            </div>

            @forelse ($pendentes as $v)
            @php
                $nomeUnidade = $unidades->get($v->unidade)?->displayed_value ?? $v->unidade;
            @endphp
            <div class="mdl-cell mdl-cell--4-col">
                <div class="mdl-card mdl-shadow--4dp" style="min-height:100px;width:100%;">
                    <div class="mdl-card__supporting-text mdl-card--border">
                        Termo: <strong>{{ $v->palavra }}</strong>
                    </div>
                    <div class="mdl-card__supporting-text mdl-card--border">
                        Solicitado por <a href="mailto:{{ $v->solicitadoPor }}">{{ $v->solicitadoPor }}</a><br>
                        Unidade: {{ $nomeUnidade }}<br>
                        Função: {{ $v->funcao }}
                    </div>
                    <div class="mdl-card__supporting-text mdl-card--border">
                        Solicitado em {{ $v->dt_solicitado?->format('d/m/Y H:i:s') }}
                    </div>
                    <div class="mdl-card__supporting-text">
                        Resumo:<br>{{ $v->resumo }}
                    </div>
                    <div class="mdl-card__actions mdl-card--border">
                        <form method="post" action="{{ route('vocabulario-controlado.solicitacao.aprovar') }}"
                              style="display:inline-block;margin-block-end:0;">
                            @csrf
                            <input type="hidden" name="acao"           value="aprovar">
                            <input type="hidden" name="vocabulario_id" value="{{ $v->id }}">
                            <input type="hidden" name="mail"           value="{{ $perfil->mail }}">
                            <button class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect">
                                Autorizar
                            </button>
                        </form>
                        <button onclick="showExcluirModal('{{ $v->id }}', `{{ addslashes($v->palavra) }}`)"
                                class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect">
                            Não Autorizar
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="mdl-cell mdl-cell--12-col">
                <p>Nenhum termo pendente de aprovação.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Aba: Lista completa --}}
    <div class="mdl-tabs__panel" id="lista-completa-apr">
        @include('VocabularioControlado::solicitacao._lista-completa')
    </div>
</div>

{{-- Dialog: Não autorizar --}}
<dialog class="mdl-dialog" id="dialog-excluir"
        style="text-align:center;height:fit-content;max-height:100%;min-width:60vw;overflow-y:hidden;">
    <h2 class="mdl-dialog__title">Não Autorizar Termo</h2>
    <form method="post" action="{{ route('vocabulario-controlado.solicitacao.aprovar') }}" id="form-excluir">
        @csrf
        <input type="hidden" name="acao"          value="excluir">
        <input type="hidden" name="idVocabulario"  id="inp-idVocabulario">
        <input type="hidden" name="termo"          id="inp-termo">
        <input type="hidden" name="mail"           value="{{ $perfil->mail }}">

        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
            <textarea class="mdl-textfield__input" name="motivoReprova" rows="3"></textarea>
            <label class="mdl-textfield__label">Justificativa</label>
        </div>
        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
            <textarea class="mdl-textfield__input" name="sugestaoPara" rows="3"></textarea>
            <label class="mdl-textfield__label">Termos sugeridos em substituição (separar por vírgula)</label>
        </div>
        <div class="mdl-dialog__actions">
            <button class="mdl-button">Confirmar</button>
            <button type="button" class="mdl-button" onclick="document.getElementById('dialog-excluir').close()">
                Fechar
            </button>
        </div>
    </form>
</dialog>

<script>
function showExcluirModal(id, palavra) {
    document.getElementById('inp-idVocabulario').value = id;
    document.getElementById('inp-termo').value = palavra;
    document.getElementById('dialog-excluir').showModal();
}
window.parent.postMessage('{"altura":"' + document.body.scrollHeight + '"}', '*');
</script>
