<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Apontamento Mobile</h2>
    </x-slot>

    <div class="py-6 px-4">
        <div class="max-w-md mx-auto">
            @if($errors->any())
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($apontamentoAtivo)
                <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6 space-y-4">
                    <div class="text-center">
                        <p class="text-xs uppercase tracking-wide text-slate-500 font-bold">Apontamento em andamento</p>
                        <p class="text-sm text-slate-700 mt-1">Descreva o que foi feito durante o dia.</p>
                    </div>

                    <form id="salvarDescricaoForm" x-data>
                        @csrf
                        <textarea 
                            name="descricao" 
                            id="descricao"
                            rows="4" 
                            class="w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500 text-sm"
                            placeholder="Descreva o que você está fazendo..."
                            x-model="$store.descricao"
                        >{{ $apontamentoAtivo->descricao }}</textarea>
                        
                        <button 
                            type="button"
                            @click="
                                fetch('{{ route('gestor-horas.mobile.save-desc') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ descricao: $store.descricao })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        alert(data.mensagem);
                                    }
                                })
                            "
                            class="mt-3 w-full bg-slate-600 hover:bg-slate-500 border border-slate-600 text-white font-semibold py-2 rounded-lg text-sm"
                        >
                            💾 Salvar Descrição
                        </button>
                    </form>

                    <form action="{{ route('gestor-horas.mobile.finish') }}"
                          method="POST"
                          class="space-y-4"
                          x-data="{ descricao: $store.descricao }">
                        @csrf
                        <input type="hidden" name="descricao" x-model="descricao">

                        <button type="submit" class="w-full bg-red-700 hover:bg-red-600 border border-red-700 text-white font-bold py-4 rounded-lg text-lg">
                            Finalizar Apontamento
                        </button>
                    </form>
                </div>

                <script>
                    document.addEventListener('alpine:init', () => {
                        Alpine.store('descricao', @json($apontamentoAtivo->descricao ?? ''));
                    });
                </script>
            @else
                <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6 space-y-4">
                    <div class="text-center">
                        <p class="text-xs uppercase tracking-wide text-slate-500 font-bold">Novo apontamento</p>
                        <p class="text-sm text-slate-700 mt-1">Selecione contrato e item para iniciar.</p>
                    </div>

                    <form action="{{ route('gestor-horas.mobile.start') }}" method="POST" class="space-y-4" x-data="{ contrato: '' }">
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Contrato</label>
                            <select name="gh_contrato_id" required x-model="contrato" class="w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                                <option value="">Selecione...</option>
                                @foreach($contratos as $contrato)
                                    <option value="{{ $contrato->id }}">{{ $contrato->titulo }} - {{ $contrato->cliente->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Item do Contrato</label>
                            <select name="gh_contrato_item_id" required class="w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                                <option value="">Selecione...</option>
                                @foreach($contratos as $contrato)
                                    @foreach($contrato->itens as $item)
                                        <option value="{{ $item->id }}" x-show="contrato == '{{ $contrato->id }}'">
                                            {{ $item->titulo }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-blue-700 hover:bg-blue-600 border border-blue-700 text-white font-bold py-4 rounded-lg text-lg">
                            Iniciar Apontamento
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
