<x-MundosDeMim::layout>
    <div class="p-6">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Galerias dos Usuários</h1>
                <p class="text-slate-400">Gerenciar fotos geradas para cada usuário</p>
            </div>
            <a href="{{ route('mundos-de-mim.index') }}"
                class="bg-white/10 text-white px-4 py-2 rounded-lg font-bold hover:bg-white/20 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Voltar ao Dashboard
            </a>
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

        <div class="glass border border-white/10 rounded-3xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-white/5 border-b border-white/10">
                    <tr>
                        <th class="px-6 py-4 text-slate-400 font-bold uppercase text-xs">Usuário</th>
                        <th class="px-6 py-4 text-slate-400 font-bold uppercase text-xs">Total de Fotos</th>
                        <th class="px-6 py-4 text-slate-400 font-bold uppercase text-xs text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $user)
                        <tr class="hover:bg-white/5 transition group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#a3e635] to-blue-500 flex items-center justify-center font-black text-black">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-white font-bold">{{ $user->name }}</div>
                                        <div class="text-slate-500 text-xs">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-[#a3e635]/10 text-[#a3e635] px-3 py-1 rounded-full text-xs font-black">
                                    {{ $user->generations_count }} fotos
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('mundos-de-mim.admin.user-gallery.show', $user->id) }}"
                                    class="inline-flex items-center bg-blue-600/20 text-blue-400 px-4 py-2 rounded-xl font-bold hover:bg-blue-600 hover:text-white transition gap-2">
                                    Ver Galeria <i class="fa-solid fa-chevron-right text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-slate-500">
                                Nenhum usuário com fotos geradas ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
    </div>
</x-MundosDeMim::layout>
