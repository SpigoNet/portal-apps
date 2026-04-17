{{-- Partial para perfil: bibliotecario --}}
@php
    $unidades = \App\Modules\VocabularioControlado\Models\ListaValores::byLista('common_publisher');
    $meusTermos = \App\Modules\VocabularioControlado\Models\Vocabulario
        ::where('solicitadoPor', $perfil->mail)
        ->orderByDesc('dt_solicitado')
        ->get();
@endphp

@if (!empty($aviso))
<div style="padding:16px;background-color:#f44336;color:#fff;margin-bottom:16px;">
    {!! $aviso !!}
</div>
@endif

<div class="mdl-tabs mdl-js-tabs">
    <div class="mdl-tabs__tab-bar" style="justify-content:flex-start;">
        <a href="#meus-termos" class="mdl-tabs__tab is-active">Meus termos solicitados</a>
        <a href="#solicitar" class="mdl-tabs__tab">Solicitar Termo</a>
        <a href="#lista-completa" class="mdl-tabs__tab">Lista Completa</a>
    </div>

    {{-- Aba: Meus termos --}}
    <div class="mdl-tabs__panel is-active" id="meus-termos">
        @if ($meusTermos->isEmpty())
            <br><p>Nenhuma solicitação encontrada.</p>
        @else
        <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp" style="width:100%;margin-top:16px;">
            <thead>
                <tr>
                    <th class="mdl-data-table__cell--non-numeric">Termo</th>
                    <th class="mdl-data-table__cell--non-numeric">Data</th>
                    <th class="mdl-data-table__cell--non-numeric">Solicitado por</th>
                    <th class="mdl-data-table__cell--non-numeric">Autorizado por</th>
                    <th class="mdl-data-table__cell--non-numeric">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($meusTermos as $v)
                <tr>
                    <td class="mdl-data-table__cell--non-numeric"
                        style="{{ $v->status === 'Não Autorizado' ? 'text-decoration:line-through;' : '' }}">
                        {{ $v->palavra }}
                    </td>
                    <td class="mdl-data-table__cell--non-numeric">
                        {{ $v->dt_solicitado?->format('d/m/Y H:i') }}
                    </td>
                    <td class="mdl-data-table__cell--non-numeric">{{ $v->solicitadoPor }}</td>
                    <td class="mdl-data-table__cell--non-numeric">{{ $v->autorizadoPor }}</td>
                    <td class="mdl-data-table__cell--non-numeric">{{ $v->status }}</td>
                </tr>
                @if ($v->status === 'Não Autorizado')
                    @php $sugestoes = $v->sugestoes() @endphp
                    @if ($sugestoes->isNotEmpty())
                    <tr>
                        <td class="mdl-data-table__cell--non-numeric" style="padding-left:48px;">
                            <strong>Sugestões para uso:</strong><br>
                            {{ $sugestoes->pluck('palavra')->implode(', ') }}
                        </td>
                        <td colspan="4" class="mdl-data-table__cell--non-numeric">
                            <strong>Justificativa:</strong><br>{{ $v->motivoReprova }}
                        </td>
                    </tr>
                    @endif
                @endif
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Aba: Solicitar --}}
    <div class="mdl-tabs__panel" id="solicitar">
        <br>
        <p>Informe o termo e o resumo em que ele é usado.</p>
        <form action="{{ route('vocabulario-controlado.solicitacao.store') }}" method="post"
              onsubmit="return validarSolicitacao(this)">
            @csrf
            <input type="hidden" name="mail"  value="{{ $perfil->mail }}">
            <input type="hidden" name="nome"  value="{{ $perfil->nome }}">

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input required class="mdl-textfield__input" type="text" id="palavra" name="palavra">
                <label class="mdl-textfield__label" for="palavra">Termo a solicitar (um termo por solicitação)</label>
            </div>
            <br>
            <div class="mdl-textfield mdl-js-textfield">
                <textarea required class="mdl-textfield__input" rows="5" id="resumo" name="resumo"></textarea>
                <label class="mdl-textfield__label" for="resumo">Resumo (para fins de contextualização)</label>
            </div>
            <div class="mdl-textfield mdl-js-textfield getmdl-select getmdl-select__fix-height">
                <input type="text" value="" class="mdl-textfield__input" id="combo-unidade" readonly name="unidade-desc">
                <input type="hidden" name="unidade" id="campo-unidade">
                <i class="mdl-icon-toggle__label material-icons">keyboard_arrow_down</i>
                <label for="combo-unidade" class="mdl-textfield__label">Selecione sua unidade</label>
                <ul for="combo-unidade" class="mdl-menu mdl-menu--bottom-left mdl-js-menu">
                    @foreach ($unidades as $u)
                    <li class="mdl-menu__item" data-val="{{ $u->stored_value }}">
                        {{ $u->displayed_value }}
                    </li>
                    @endforeach
                </ul>
            </div>
            <br>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input required class="mdl-textfield__input" type="text" id="funcao" name="funcao" maxlength="50">
                <label class="mdl-textfield__label" for="funcao">Função na unidade</label>
            </div>
            <br>
            <input type="submit" id="btn-submit" value="Solicitar"
                   class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
        </form>
    </div>

    {{-- Aba: Lista completa --}}
    <div class="mdl-tabs__panel" id="lista-completa">
        @include('VocabularioControlado::solicitacao._lista-completa')
    </div>
</div>

<script>
function validarSolicitacao(form) {
    if (!form.checkValidity()) return false;
    if (document.getElementById('campo-unidade').value === '') {
        alert('Selecione a unidade');
        return false;
    }
    document.getElementById('btn-submit').disabled = true;
    document.getElementById('btn-submit').value = 'Aguarde…';
    return true;
}
</script>
