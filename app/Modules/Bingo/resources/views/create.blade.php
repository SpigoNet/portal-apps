<x-Bingo::layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-6" x-data="createBingo()">
        <div class="w-full max-w-lg mx-auto bg-white/90 backdrop-blur rounded-3xl p-8 shadow-xl border border-amber-200">
            <div class="text-center mb-6">
                <div class="text-5xl mb-2">🎯</div>
                <h1 class="text-3xl font-black text-amber-800">Nova Partida</h1>
                <p class="text-amber-600/70 text-sm">Configure sua partida de bingo</p>
            </div>

            <form x-on:submit.prevent="criar()" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-amber-800 mb-3">
                        🎨 Escolha o Tema
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="tema in temas" :key="tema">
                            <button type="button"
                                    x-on:click="temaSelecionado = tema"
                                    :class="{'ring-4 ring-emerald-400 border-emerald-500 scale-105': temaSelecionado === tema, 'border-amber-200 hover:border-amber-300': temaSelecionado !== tema}"
                                    class="p-4 rounded-2xl border-2 bg-gradient-to-br from-amber-50 to-orange-50 text-center transition-all duration-200">
                                <div class="text-2xl mb-1">🖼️</div>
                                <div class="text-sm font-bold text-amber-800" x-text="tema.replace('.png','')"></div>
                            </button>
                        </template>
                    </div>
                    <p x-show="!temaSelecionado" class="text-xs text-rose-500 mt-1">Selecione um tema</p>
                </div>

                <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl p-4 border border-amber-200">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="modoGestor" class="w-5 h-5 accent-amber-500 rounded">
                        <div>
                            <span class="text-sm font-bold text-amber-800 block">🎮 Só gerenciar sorteios</span>
                            <span class="text-xs text-amber-600/70">Não quero jogar, só sortear os números</span>
                        </div>
                    </label>
                </div>

                <button type="submit"
                        :disabled="!temaSelecionado || carregando"
                        :class="{'opacity-50 cursor-not-allowed': !temaSelecionado || carregando}"
                        class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold py-4 px-6 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200 text-lg">
                    <span x-show="!carregando">🚀 Criar Partida!</span>
                    <span x-show="carregando">⏳ Criando...</span>
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('bingo.index') }}" class="text-sm text-amber-600 hover:text-amber-800 underline underline-offset-2">
                    ← Voltar
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('createBingo', () => ({
                temas: @json($temas),
                temaSelecionado: '',
                modoGestor: false,
                carregando: false,

                async criar() {
                    if (!this.temaSelecionado || this.carregando) return;
                    this.carregando = true;

                    try {
                        const resp = await fetch('{{ route('bingo.store') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({
                                tema: this.temaSelecionado,
                                modo_gestor: this.modoGestor,
                            })
                        });

                        if (!resp.ok) throw new Error('Erro ao criar partida');

                        const data = await resp.json();
                        localStorage.setItem('bingo_dono_token', data.dono_token);

                        if (data.cartela) {
                            localStorage.setItem('bingo_token', data.dono_token);
                        }

                        window.location.href = '/bingo/' + data.codigo;
                    } catch (e) {
                        alert('Erro: ' + e.message);
                        this.carregando = false;
                    }
                }
            }));
        });
    </script>
    @endpush
</x-Bingo::layout>
