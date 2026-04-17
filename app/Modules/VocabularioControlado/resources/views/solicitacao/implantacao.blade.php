{{-- Partial para perfil: implantacao --}}
<div class="space-y-4">
    <form method="post" action="{{ route('vocabulario-controlado.solicitacao.implantacao') }}">
        <input type="hidden" name="mail" value="{{ $perfil->mail }}">
        <input type="hidden" name="acao" value="marcar">
        <button class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-5 py-2 rounded-lg transition">
            Marcar todos os aprovados como Disponível
        </button>
    </form>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500 mb-3">XML para integração</p>
        <textarea rows="20" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-mono"><?php
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<node id="riccps" label="">' . "\n";
echo '    <isComposedBy>' . "\n";
foreach ($termos as $t):
    echo '        <node label="' . htmlspecialchars(trim($t->palavra), ENT_XML1) . '" id="' . $t->id . '"></node>' . "\n";
endforeach;
echo '    </isComposedBy>' . "\n";
echo '</node>';
?></textarea>
    </div>
</div>
