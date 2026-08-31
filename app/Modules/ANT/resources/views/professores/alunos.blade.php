<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.professor.index') }}" class="text-gray-500 hover:text-gray-900">Dashboard</a>
            <span class="text-gray-400 mx-2">/</span>
            Alunos <span class="text-sm font-normal text-gray-500">| {{ $semestreAtual }}</span>
        </h2>
    </x-slot>

    <div x-data="{ ra: '', nome: '' }">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-50 p-4 rounded-md border border-green-200">
                    <p class="text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 p-4 rounded-md border border-red-200">
                    <p class="text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Filtros --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4">Filtros</h3>
                    <form method="GET" action="{{ route('ant.professor.alunos') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <x-input-label for="ra" value="RA" />
                            <x-text-input id="ra" name="ra" type="text" class="mt-1 block w-full" :value="$ra"
                                placeholder="Filtrar por RA" />
                        </div>
                        <div>
                            <x-input-label for="email" value="E-mail" />
                            <x-text-input id="email" name="email" type="text" class="mt-1 block w-full" :value="$email"
                                placeholder="Filtrar por e-mail" />
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button type="submit">Filtrar</x-primary-button>
                            <a href="{{ route('ant.professor.alunos') }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                                Limpar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Lista de Alunos --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vínculo</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($alunos as $aluno)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $aluno->nome }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                        {{ $aluno->ra }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($aluno->user)
                                            {{ $aluno->user->email }}
                                        @else
                                            <span class="text-gray-300 italic">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($aluno->user_id)
                                            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs font-bold">Vinculado</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold">Não vinculado</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if($aluno->user_id)
                                            <button type="button"
                                                x-on:click="ra = '{{ $aluno->ra }}'; nome = '{{ addslashes($aluno->nome) }}'; $dispatch('open-modal', 'resetar-senha-aluno')"
                                                class="text-amber-600 hover:text-amber-900 font-bold">Resetar Senha</button>
                                        @else
                                            <span class="text-gray-300 italic">Indisponível</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                                        Nenhum aluno encontrado com os filtros informados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <x-modal name="resetar-senha-aluno" :show="false" focusable>
        <form method="POST" action="{{ route('ant.professor.resetar_senha_aluno') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-bold text-gray-800 mb-1">Resetar Senha do Aluno</h2>
            <p class="text-sm text-gray-500 mb-1">
                Defina uma senha específica para <span class="font-bold text-gray-700" x-text="nome"></span>.
            </p>
            <p class="text-xs text-gray-400 mb-4 font-mono" x-text="ra"></p>

            <input type="hidden" name="ra" :value="ra" />

            <div class="mb-4">
                <x-input-label for="password" value="Nova Senha" />
                <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" required
                    placeholder="Senha específica" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button type="button"
                    onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'resetar-senha-aluno' }))">
                    Cancelar
                </x-secondary-button>
                <x-primary-button type="submit">
                    Redefinir Senha
                </x-primary-button>
            </div>
        </form>
    </x-modal>
    </div>
</x-ANT::layout>
