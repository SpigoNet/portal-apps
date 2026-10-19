@extends('Pidgey::components.layout')

@section('content')
    <div class="space-y-8">
        <header class="border-b border-white/10 pb-4">
            <h1 class="text-2xl font-black tracking-tight text-amber-400">
                <i class="fa-solid fa-sliders mr-2"></i>Parâmetros de imagem
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Valores enviados junto com toda imagem de personagem gerada pelo Pidgey para o provedor.
                Campos em branco não são enviados (o provedor usa o padrão dele).
            </p>
        </header>

        @forelse ($provedores as $provedor)
            @php $valores = $parametros[$provedor->id]->parametros ?? []; @endphp
            <form method="POST" action="{{ route('pidgey.parametros-imagem.update', $provedor->id) }}"
                class="rounded-2xl border border-white/10 bg-white/5 p-6">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-bold text-slate-200">{{ $provedor->nome }}</h2>
                <p class="text-xs text-slate-500 mb-5">{{ $provedor->base_url }}</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ([
                        'quality' => 'Qualidade',
                        'background' => 'Fundo',
                        'output_format' => 'Formato de saída',
                        'moderation' => 'Moderação',
                        'style' => 'Estilo',
                        'input_fidelity' => 'Fidelidade à imagem de referência',
                        'reasoning_effort' => 'Esforço de raciocínio',
                    ] as $campo => $rotulo)
                        <label class="block">
                            <span class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">{{ $rotulo }}</span>
                            <select name="{{ $campo }}"
                                class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400">
                                <option value="">— padrão do provedor —</option>
                                @foreach ($opcoes[$campo] as $valor => $nome)
                                    <option value="{{ $valor }}" @selected(($valores[$campo] ?? null) === $valor)>{{ $nome }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach

                    <label class="block">
                        <span class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Tamanho (LxA)</span>
                        <input type="text" name="size" value="{{ $valores['size'] ?? '' }}" placeholder="1024x1024"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
                    </label>

                    <label class="block">
                        <span class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Compressão (0–100)</span>
                        <input type="number" min="0" max="100" name="output_compression" value="{{ $valores['output_compression'] ?? '' }}"
                            class="w-full rounded-lg bg-slate-900 border border-white/10 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:ring-amber-400" />
                    </label>
                </div>

                <p class="text-[11px] text-slate-500 mt-4">
                    Fundo transparente não combina com JPEG (o fundo é descartado nesse caso). O Telegram envia a foto
                    recomprimida; tamanhos menores e qualidade média já ficam bons no celular.
                </p>

                <button type="submit"
                    class="mt-5 inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-black uppercase tracking-widest text-slate-950 hover:bg-amber-400 transition">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar
                </button>
            </form>
        @empty
            <p class="text-sm text-slate-500">Nenhum provedor ativo com driver OpenAI-compatível (ex.: o gateway do Codex).</p>
        @endforelse
    </div>
@endsection
