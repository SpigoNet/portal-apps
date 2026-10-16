<x-Cardapio::layout titulo="Conexão MCP">
    <h1 class="text-2xl font-extrabold">🔌 Conexão MCP</h1>
    <p class="text-sm">Use este endereço para outros agentes (Claude, Cursor, etc.) consultarem e alterarem o seu Cardápio. O token já está na URL, então não precisa configurar headers.</p>

    <section class="cd-card space-y-3" x-data="{ copiado: null, copiar(t, k) { navigator.clipboard.writeText(t); this.copiado = k; setTimeout(() => this.copiado = null, 1500) } }">
        <h2 class="font-extrabold text-lg">Endereço do servidor</h2>
        <div class="flex flex-wrap gap-2 items-center">
            <input type="text" readonly value="{{ $url }}" class="flex-1 min-w-0 border rounded px-2 py-2 font-mono text-xs" onclick="this.select()">
            <button type="button" class="cd-btn" @click="copiar(@js($url), 'url')" x-text="copiado === 'url' ? 'Copiado ✔' : 'Copiar'"></button>
        </div>
        <p class="text-sm text-red-700 font-bold">⚠ Quem tiver este endereço acessa e altera os dados da sua casa. Não compartilhe. O token muda se você trocar a senha.</p>

        <h3 class="font-bold pt-2">Claude Code</h3>
        @php($cmd = 'claude mcp add --transport http cardapio '.$url)
        <div class="flex flex-wrap gap-2 items-start">
            <pre class="flex-1 min-w-0 bg-green-50 rounded p-2 text-xs overflow-x-auto">{{ $cmd }}</pre>
            <button type="button" class="cd-btn" @click="copiar(@js($cmd), 'cmd')" x-text="copiado === 'cmd' ? 'Copiado ✔' : 'Copiar'"></button>
        </div>

        <h3 class="font-bold pt-2">Outros clientes (JSON)</h3>
        @php($json = json_encode(['mcpServers' => ['cardapio' => ['type' => 'http', 'url' => $url]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
        <div class="flex flex-wrap gap-2 items-start">
            <pre class="flex-1 min-w-0 bg-green-50 rounded p-2 text-xs overflow-x-auto">{{ $json }}</pre>
            <button type="button" class="cd-btn" @click="copiar(@js($json), 'json')" x-text="copiado === 'json' ? 'Copiado ✔' : 'Copiar'"></button>
        </div>
    </section>

    <section class="cd-card space-y-3" x-data="{ copiado: false }">
        <h2 class="font-extrabold text-lg">Prompt de contexto sugerido</h2>
        <p class="text-sm">Cole nas instruções do agente (system prompt, CLAUDE.md, instruções do projeto) para ele usar as ferramentas do jeito certo.</p>
        <button type="button" class="cd-btn" @click="navigator.clipboard.writeText(@js($prompt)); copiado = true; setTimeout(() => copiado = false, 1500)" x-text="copiado ? 'Copiado ✔' : 'Copiar prompt'"></button>
        <pre class="bg-green-50 rounded p-2 text-xs whitespace-pre-wrap">{{ $prompt }}</pre>
    </section>

    <section class="cd-card space-y-2">
        <h2 class="font-extrabold text-lg">Ferramentas disponíveis</h2>
        @foreach ($ferramentas as $f)
            <div class="border-t border-green-100 pt-2 first:border-0 first:pt-0">
                <span class="font-mono font-bold text-sm">{{ $f['name'] }}</span>
                <span class="cd-chip {{ $f['annotations']['readOnlyHint'] ? '' : 'cd-chip-aviso' }}">{{ $f['annotations']['readOnlyHint'] ? 'leitura' : 'altera dados' }}</span>
                <p class="text-sm">{{ $f['description'] }}</p>
            </div>
        @endforeach
    </section>
</x-Cardapio::layout>
