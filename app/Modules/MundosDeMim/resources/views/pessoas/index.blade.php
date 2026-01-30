<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pessoas Relacionadas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="flex justify-end mb-4">
                <a href="{{ route('mundos-de-mim.pessoas.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-500">
                    + Adicionar Pessoa
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($pessoas->isEmpty())
                        <p class="text-gray-500">Nenhuma pessoa cadastrada. Adicione alguém para gerar artes em dupla!</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pessoa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Relacionamento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status na IA</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($pessoas as $pessoa)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover border border-gray-200"
                                                     src="{{ Storage::url($pessoa->photo_path) }}"
                                                     alt="{{ $pessoa->name }}">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $pessoa->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-500">{{ $pessoa->relationship }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($pessoa->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        Ativo (Pode aparecer)
                    </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                        Pausado
                    </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="{{ route('mundos-de-mim.pessoas.toggle', $pessoa->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-bold">
                                                {{ $pessoa->is_active ? 'Pausar' : 'Ativar' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                        <p class="mb-2">Você ainda não adicionou ninguém.</p>
                                        <p class="text-sm">Adicione amigos ou familiares para gerar artes temáticas (ex: Natal, Festas).</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
