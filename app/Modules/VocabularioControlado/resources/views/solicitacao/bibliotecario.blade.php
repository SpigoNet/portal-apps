{{-- Partial para perfil: bibliotecario --}}
@php
    $unidades = \App\Modules\VocabularioControlado\Models\ListaValores::byLista('common_publisher');
    $meusTermos = \App\Modules\VocabularioControlado\Models\Vocabulario
        ::where('solicitadoPor', $perfil->mail)
        ->orderByDesc('dt_solicitado')
        ->get();
@endphp

@if (!empty($aviso))
<div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
    {!! $aviso !!}
</div>
@endif

<div x-data="{ aba: 'meus' }">
    <div class="flex border-b border-gray-200 mb-6 gap-1">
        <button @click="aba='meus'" :class="aba==='meus' ? 'border-b-2 border-blue-700 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition">Meus termos solicitados</button>
        <button @click="aba='solicitar'" :class="aba==='solicitar' ? 'border-b-2 border-blue-700 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition">Solicitar Termo</button>
        <button @click="aba='lista'" :class="aba==='lista' ? 'border-b-2 border-blue-700 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition">Lista Completa</button>
    </div>

    <div x-show="aba==='meus'" x-cloak>
        @if ($meusTermos->isEmpty())
            <p class="text-gray-500 text-sm">Nenhuma solicitação encontrada.</p>
        @else
        <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Termo</th>
                        <th class="px-4 py-3 text-left">Data</th>
                        <th class="px-4 py-3 text-left">Solicitado por</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($meusTermos as $v)
                    @php
                        $statusCores = [
                            'Disponível'     => 'bg-green-100 text-green-800',
                            'Aprovado'       => 'bg-blue-100 text-blue-800',
                            'Solicitado'     => 'bg-yellow-100 text-yellow-800',
                            'Não Autorizado' => 'bg-red-100 text-red-800',
                        ];
                        $cor = $statusCores[$v->status] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium {{ $v->status === 'Não Autorizado' ? 'line-through text-gray-400' : '' }}">{{ $v->palavra }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $v->dt_solicitado?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $v->solicitadoPor }}</td>
                        <td class="px-4 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $cor }}">{{ $v->status }}</span></td>
                    </tr>
                    @if ($v->status === 'Não Autorizado')
                        @php $sugestoes = $v->sugestoes() @endphp
                        @if ($sugestoes->isNotEmpty())
                        <tr class="bg-red-50">
                            <td class="px-4 py-2 pl-8 text-xs text-gray-600" colspan="2"><span class="font-semibold">Sugestões:</span> {{ $sugestoes->pluck('palavra')->implode(', ') }}</td>
                            <td class="px-4 py-2 text-xs text-gray-600" colspan="2"><span class="font-semibold">Justificativa:</span> {{ $v->motivoReprova }}</td>
                        </tr>
                        @endif
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div x-show="aba==='solicitar'" x-cloak>
        <p class="text-sm text-gray-600 mb-4">Informe o termo e o resumo em que ele é usado.</p>
        <form action="{{ route('vocabulario-controlado.solicitacao.store') }}" method="post" class="space-y-4 max-w-xl">
            <input type="hidden" name="mail" value="{{ $perfil->mail }}">
            <input type="hidden" name="nome" value="{{ $perfil->nome }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Termo <span class="text-red-500">*</span></label>
                <input required type="text" name="palavra" value="{{ old('palavra') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Resumo <span class="text-red-500">*</span></label>
                <textarea required name="resumo" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('resumo') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unidade <span class="text-red-500">*</span></label>
                <select required name="unidade"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione...</option>
                    @foreach ($unidades as $u)
                    <option value="{{ $u->stored_value }}">{{ $u->displayed_value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Função na unidade <span class="text-red-500">*</span></label>
                <input required type="text" name="funcao" maxlength="50" value="{{ old('funcao') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-6 py-2 rounded-lg transition">Solicitar</button>
        </form>
    </div>

    <div x-show="aba==='lista'" x-cloak>
        @include('VocabularioControlado::solicitacao._lista-completa')
    </div>
</div>
