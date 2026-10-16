<x-MundosDeMim::layout>
    <div class="p-6">
        <header class="mb-8">
            <h1 class="text-3xl font-black text-white">Parâmetros de imagem</h1>
            <p class="text-slate-400">
                Valores pré-preenchidos enviados junto com toda imagem gerada pelo módulo para o provedor.
                Campos em branco não são enviados (o provedor usa o padrão dele).
            </p>
        </header>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 text-green-400 rounded-xl">{{ session('success') }}</div>
        @endif

        @forelse($provedores as $provedor)
            @php $valores = $parametros[$provedor->id]->parametros ?? []; @endphp
            <form method="POST" action="{{ route('mundos-de-mim.admin.parametros-imagem.update', $provedor->id) }}"
                  class="mb-8 p-6 rounded-2xl bg-slate-800/60 border border-slate-700">
                @csrf
                @method('PUT')
                <h2 class="text-xl font-bold text-white mb-1">{{ $provedor->nome }}</h2>
                <p class="text-xs text-slate-400 mb-5">{{ $provedor->base_url }}</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach([
                        'quality' => 'Qualidade',
                        'background' => 'Fundo',
                        'output_format' => 'Formato de saída',
                        'moderation' => 'Moderação',
                        'style' => 'Estilo',
                        'input_fidelity' => 'Fidelidade à imagem de entrada',
                        'reasoning_effort' => 'Esforço de raciocínio',
                    ] as $campo => $rotulo)
                        <label class="block text-sm text-slate-300">
                            {{ $rotulo }}
                            <select name="{{ $campo }}" class="mt-1 w-full rounded-lg bg-slate-900 border border-slate-600 text-white px-3 py-2">
                                <option value="">— padrão do provedor —</option>
                                @foreach($opcoes[$campo] as $valor => $nome)
                                    <option value="{{ $valor }}" @selected(($valores[$campo] ?? null) === $valor)>{{ $nome }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach

                    <label class="block text-sm text-slate-300">
                        Tamanho (LxA)
                        <input type="text" name="size" value="{{ $valores['size'] ?? '' }}" placeholder="1024x1536"
                               class="mt-1 w-full rounded-lg bg-slate-900 border border-slate-600 text-white px-3 py-2">
                    </label>

                    <label class="block text-sm text-slate-300">
                        Compressão (0–100)
                        <input type="number" min="0" max="100" name="output_compression" value="{{ $valores['output_compression'] ?? '' }}"
                               class="mt-1 w-full rounded-lg bg-slate-900 border border-slate-600 text-white px-3 py-2">
                    </label>
                </div>

                <p class="text-xs text-slate-500 mt-4">Fundo transparente não combina com JPEG (o fundo é descartado nesse caso).</p>

                <button type="submit" class="mt-5 bg-[#FF7A59] text-white px-5 py-2 rounded-lg font-bold hover:opacity-90">
                    Salvar
                </button>
            </form>
        @empty
            <p class="text-slate-400">Nenhum provedor ativo com driver OpenAI-compatível (ex.: o gateway do Codex).</p>
        @endforelse
    </div>
</x-MundosDeMim::layout>
