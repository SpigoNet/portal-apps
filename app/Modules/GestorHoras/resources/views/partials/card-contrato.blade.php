@php
    $consumido = $contrato->horas_consumidas;
    // Para calcular % corretamente no visual, usamos o maior valor entre contratado e itens somados
    $totalBase = $contrato->tipo == 'fixo' ? $contrato->horas_contratadas : $contrato->itens->sum('horas_estimadas');
    if($totalBase == 0) $totalBase = $contrato->horas_contratadas; // Fallback

    $porcentagem = $totalBase > 0 ? ($consumido / $totalBase) * 100 : 0;

    // Cores
    if ($inativo) {
        $corBarra = 'bg-gray-400';
        $bgCard = $contrato->status == 'cancelado' ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200';
    } else {
        $corBarra = $porcentagem > 100 ? 'bg-red-500' : ($porcentagem > 85 ? 'bg-yellow-500' : 'bg-green-500');
        $bgCard = 'bg-white border-gray-200';
    }
@endphp

<div class="{{ $bgCard }} overflow-hidden shadow-sm sm:rounded-lg border hover:shadow-md transition relative">

    @if($contrato->status == 'finalizado')
        <div class="absolute top-0 right-0 bg-gray-600 text-white text-xs px-2 py-1 rounded-bl-lg font-bold uppercase">Concluído</div>
    @elseif($contrato->status == 'cancelado')
        <div class="absolute top-0 right-0 bg-red-600 text-white text-xs px-2 py-1 rounded-bl-lg font-bold uppercase">Cancelado</div>
    @endif

    <div class="p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <span class="inline-block px-2 py-1 text-xs font-semibold rounded mb-1
                    {{ $contrato->tipo == 'recorrente' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                    {{ ucfirst($contrato->tipo) }}
                </span>
                @if(auth()->user()->gh_role !== 'client')
                    <div class="text-xs text-gray-500 font-bold">{{ $contrato->cliente->nome }}</div>
                @endif
            </div>
            <span class="text-xs text-gray-400">
                Início: {{ $contrato->data_inicio->format('d/m/Y') }}
            </span>
        </div>

        <h3 class="text-lg font-bold {{ $inativo ? 'text-gray-600' : 'text-gray-900' }} mb-2 truncate">
            <a href="{{ route('gestor-horas.show', $contrato->id) }}" class="hover:underline">
                {{ $contrato->titulo }}
            </a>
        </h3>

        <div class="mt-4">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-medium text-gray-500">Consumo Geral</span>
                <span class="font-bold {{ !$inativo && $consumido > $totalBase ? 'text-red-600' : 'text-gray-700' }}">
                    {{ number_format($consumido, 2, ',', '.') }}h
                    <span class="text-xs font-normal text-gray-400">/ {{ number_format($totalBase, 2, ',', '.') }}h</span>
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="{{ $corBarra }} h-2 rounded-full" style="width: {{ min($porcentagem, 100) }}%"></div>
            </div>
        </div>

        <div class="mt-6 flex justify-between items-center">

            @if(auth()->user()->gh_role === 'admin')
                <div class="text-xs text-gray-400 cursor-pointer hover:text-blue-500"
                     onclick="navigator.clipboard.writeText('{{ $contrato->cliente->public_link }}'); alert('Link copiado!')"
                     title="Copiar Link do Cliente">
                    Link Público 🔗
                </div>
            @else
                <div></div>
            @endif

            <a href="{{ route('gestor-horas.show', $contrato->id) }}" class="{{ $inativo ? 'text-gray-500' : 'text-blue-600' }} hover:underline text-sm font-medium">
                {{ $inativo ? 'Ver Histórico' : 'Gerenciar' }} &rarr;
            </a>
        </div>
    </div>
</div>
