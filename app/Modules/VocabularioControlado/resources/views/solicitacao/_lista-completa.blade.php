{{-- Partial: lista pública de termos aprovados/disponíveis --}}
@php
    $palavraLista = trim((string) request()->query('palavra_lista', ''));
    $listaId = 'lista-'.uniqid();

    $query = \App\Modules\VocabularioControlado\Models\Vocabulario
        ::whereIn('status', ['Disponível', 'Aprovado'])
        ->orderByRaw('TRIM(palavra)');

    if ($palavraLista !== '') {
        $query->where('palavra', 'LIKE', '%'.$palavraLista.'%');
    }

    $listaTermos = $query->simplePaginate(50, ['*'], 'pagina_lista');
@endphp

<form action="{{ route('vocabulario-controlado.solicitacao') }}" method="get"
      class="flex gap-2 mb-4">
    @if(request()->filled('mail'))
    <input type="hidden" name="mail" value="{{ request()->query('mail') }}">
    @endif
    @if(request()->filled('nome'))
    <input type="hidden" name="nome" value="{{ request()->query('nome') }}">
    @endif

    <input type="text" name="palavra_lista" value="{{ $palavraLista }}" placeholder="Filtrar lista..."
           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit"
            class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg">
        Pesquisar
    </button>
    @if($palavraLista !== '')
    <a href="{{ route('vocabulario-controlado.solicitacao', request()->only(['mail', 'nome'])) }}"
       class="border border-gray-300 hover:bg-gray-100 text-gray-700 text-sm px-4 py-2 rounded-lg">
        Limpar
    </a>
    @endif
</form>

<div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">Termo</th>
                <th class="px-4 py-3 text-left">Status</th>
            </tr>
        </thead>
        <tbody id="{{ $listaId }}-tbody" class="divide-y divide-gray-100">
            @foreach ($listaTermos as $t)
            <tr data-lista-item="1" class="hover:bg-gray-50">
                <td class="px-4 py-2 font-medium">{{ $t->palavra }}</td>
                <td class="px-4 py-2">
                    <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800">{{ $t->status }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@php
    $proximaUrl = $listaTermos->hasMorePages()
        ? route('vocabulario-controlado.solicitacao.lista-fragmento', array_merge(
            request()->only(['mail', 'nome']),
            ['palavra_lista' => $palavraLista, 'pagina_lista' => $listaTermos->currentPage() + 1]
        ))
        : null;
@endphp

<div id="{{ $listaId }}-status" class="text-xs text-gray-500 mt-3">
    Exibindo {{ $listaTermos->count() }} de {{ $listaTermos->count() + (($listaTermos->currentPage() - 1) * 50) }}+ (carregando 50 por vez)
</div>

<div id="{{ $listaId }}-sentinela" data-next-url="{{ $proximaUrl }}" class="h-8"></div>

<script>
(() => {
    const tbody = document.getElementById('{{ $listaId }}-tbody');
    const sentinela = document.getElementById('{{ $listaId }}-sentinela');
    const status = document.getElementById('{{ $listaId }}-status');
    if (!tbody || !sentinela) return;

    let carregando = false;
    let nextUrl = sentinela.dataset.nextUrl || '';
    let totalRenderizado = tbody.querySelectorAll('tr[data-lista-item="1"]').length;

    if (!nextUrl) {
        status.textContent = `Total carregado: ${totalRenderizado}`;
        return;
    }

    async function carregarMais() {
        if (carregando || !nextUrl) return;
        carregando = true;
        status.textContent = `Carregando mais termos... (${totalRenderizado})`;

        try {
            const resposta = await fetch(nextUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!resposta.ok) {
                throw new Error('Falha ao carregar mais termos');
            }

            const html = await resposta.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const novasLinhas = doc.querySelectorAll('#fragment-rows tr[data-lista-item="1"], tr[data-lista-item="1"]');
            novasLinhas.forEach((linha) => tbody.appendChild(linha));
            totalRenderizado += novasLinhas.length;

            const proximo = doc.querySelector('[data-next-url]');
            nextUrl = proximo ? (proximo.getAttribute('data-next-url') || '') : '';
            sentinela.dataset.nextUrl = nextUrl;

            if (!nextUrl) {
                status.textContent = `Total carregado: ${totalRenderizado}`;
                observer.disconnect();
            } else {
                status.textContent = `Carregados ${totalRenderizado} termos (rolagem infinita ativa)`;
            }
        } catch (e) {
            status.textContent = 'Erro ao carregar mais termos. Role novamente para tentar.';
        } finally {
            carregando = false;
        }
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                carregarMais();
            }
        });
    }, {
        rootMargin: '400px 0px',
    });

    observer.observe(sentinela);
})();
</script>
