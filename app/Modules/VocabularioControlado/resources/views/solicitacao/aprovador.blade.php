{{-- Partial para perfil: aprovador --}}
<div x-data="{ aba: 'aprovar', modalExcluir: false, excluirId: null, excluirTermo: '' }">
    <div class="flex border-b border-gray-200 mb-6 gap-1">
        <button @click="aba='aprovar'" :class="aba==='aprovar' ? 'border-b-2 border-blue-700 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition">Autorizar termos</button>
        <button @click="aba='lista'" :class="aba==='lista' ? 'border-b-2 border-blue-700 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition">Lista Completa</button>
    </div>

    <div x-show="aba==='aprovar'" x-cloak>
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Lista de termos a serem aprovados</h2>

        @if($pendentes->isEmpty())
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-500">Nenhum termo pendente de aprovação.</div>
        @else
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($pendentes as $v)
                @php $nomeUnidade = $unidades->get($v->unidade)?->displayed_value ?? $v->unidade; @endphp
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-col gap-3">
                    <div>
                        <p class="text-xs uppercase text-gray-400">Termo</p>
                        <p class="font-semibold text-gray-800">{{ $v->palavra }}</p>
                    </div>
                    <div class="text-sm text-gray-600">
                        <p>Solicitado por: <a href="mailto:{{ $v->solicitadoPor }}" class="text-blue-600 hover:underline">{{ $v->solicitadoPor }}</a></p>
                        <p>Unidade: {{ $nomeUnidade }}</p>
                        <p>Função: {{ $v->funcao }}</p>
                        <p>Data: {{ $v->dt_solicitado?->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div class="text-sm text-gray-700 bg-gray-50 rounded p-2 border border-gray-100">{{ $v->resumo }}</div>

                    <div class="flex gap-2 pt-2">
                        <form method="post" action="{{ route('vocabulario-controlado.solicitacao.aprovar') }}">
                            <input type="hidden" name="acao" value="aprovar">
                            <input type="hidden" name="vocabulario_id" value="{{ $v->id }}">
                            <input type="hidden" name="mail" value="{{ $perfil->mail }}">
                            <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-2 rounded-lg transition">Autorizar</button>
                        </form>

                        <button
                            @click="modalExcluir = true; excluirId = {{ $v->id }}; excluirTermo = '{{ addslashes($v->palavra) }}'"
                            class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-2 rounded-lg transition">
                            Não Autorizar
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <div x-show="aba==='lista'" x-cloak>
        @include('VocabularioControlado::solicitacao._lista-completa')
    </div>

    <div x-show="modalExcluir" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6" @click.outside="modalExcluir = false">
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Não autorizar termo</h3>
            <p class="text-sm text-gray-500 mb-4">Termo: <strong x-text="excluirTermo"></strong></p>

            <form method="post" action="{{ route('vocabulario-controlado.solicitacao.aprovar') }}" class="space-y-3">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="idVocabulario" :value="excluirId">
                <input type="hidden" name="termo" :value="excluirTermo">
                <input type="hidden" name="mail" value="{{ $perfil->mail }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Justificativa</label>
                    <textarea name="motivoReprova" rows="3" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Termos sugeridos (vírgula)</label>
                    <textarea name="sugestaoPara" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modalExcluir=false" class="border border-gray-300 hover:bg-gray-100 text-gray-700 text-sm px-4 py-2 rounded-lg">Cancelar</button>
                    <button class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg">Confirmar não autorização</button>
                </div>
            </form>
        </div>
    </div>
</div>
