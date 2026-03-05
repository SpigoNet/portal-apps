<x-MundosDeMim::layout>
    <div class="p-6">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <a href="{{ route('mundos-de-mim.admin.user-gallery.index') }}"
                    class="text-[#a3e635] font-bold flex items-center gap-2 mb-2 hover:underline">
                    <i class="fa-solid fa-arrow-left"></i> Voltar para Lista de Usuários
                </a>
                <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Galeria de {{ $user->name }}</h1>
                <p class="text-slate-400">Total de {{ $generations->total() }} fotos geradas</p>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 text-green-400 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 text-red-400 rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        @if ($generations->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($generations as $gen)
                    <div
                        class="group relative rounded-3xl overflow-hidden border border-white/10 glass flex flex-col bg-white/5">
                        <div class="aspect-square relative overflow-hidden">
                            <img src="{{ $gen->image_url }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition duration-500">

                            <div
                                class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-3">
                                <form action="{{ route('mundos-de-mim.admin.user-gallery.send', $gen->id) }}"
                                    method="POST">
                                    @csrf
                                    <button type="submit" title="Reenviar para o Usuário"
                                        class="bg-[#a3e635] text-black p-4 rounded-2xl hover:scale-110 transition">
                                        <i class="fa-solid fa-paper-plane text-xl"></i>
                                    </button>
                                </form>

                                <form action="{{ route('mundos-de-mim.admin.user-gallery.destroy', $gen->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir esta foto permanentemente?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Excluir Foto"
                                        class="bg-red-600 text-white p-4 rounded-2xl hover:scale-110 transition">
                                        <i class="fa-solid fa-trash text-xl"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="p-4 border-t border-white/5">
                            <div class="flex justify-between items-start mb-1">
                                <span
                                    class="bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider">
                                    {{ $gen->theme->name ?? 'Sem Tema' }}
                                </span>
                                <span class="text-slate-500 text-[10px] font-bold">
                                    {{ $gen->reference_date->format('d/m/Y') }}
                                </span>
                            </div>
                            <p class="text-white text-xs line-clamp-2 text-slate-400 italic">
                                "{{ $gen->final_prompt_used }}"
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $generations->links() }}
            </div>
        @else
            <div class="p-20 text-center glass rounded-3xl border border-dashed border-white/20">
                <i class="fa-solid fa-image text-white/10 text-6xl mb-4"></i>
                <p class="text-slate-400">Nenhuma foto encontrada para este usuário.</p>
            </div>
        @endif
    </div>
</x-MundosDeMim::layout>
