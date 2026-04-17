{{-- Partial para perfil: implantacao --}}
<form method="post" action="{{ route('vocabulario-controlado.solicitacao.implantacao') }}">
    @csrf
    <input type="hidden" name="mail"  value="{{ $perfil->mail }}">
    <input type="hidden" name="acao"  value="marcar">
    <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
        Marcar todos como liberados
    </button>
</form>

<br><br>

<textarea rows="20" cols="100" spellcheck="false"><?php
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<node id="riccps" label="">' . "\n";
echo '    <isComposedBy>' . "\n";
foreach ($termos as $t):
    echo '        <node label="' . htmlspecialchars(trim($t->palavra), ENT_XML1) . '" id="' . $t->id . '"></node>' . "\n";
endforeach;
echo '    </isComposedBy>' . "\n";
echo '</node>';
?></textarea>
