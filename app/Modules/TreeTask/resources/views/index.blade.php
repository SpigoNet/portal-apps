<x-TreeTask::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight text-gray-400 hover:text-white">
                {{ __('TreeTask - Projetos') }}
            </h2>
        </div>
    </x-slot>

    {{-- Menu de Contexto (Botões do Topo) --}}
    <x-slot name="contextMenu">
        <x-dropdown-link :href="route('treetask.focus.index')">
            Foco (Modo Zen 🧘)
        </x-dropdown-link>
        <x-dropdown-link :href="route('treetask.good_morning')">
            Bom Dia ☀️
        </x-dropdown-link>
        <x-dropdown-link :href="route('treetask.create')">
            + Novo Projeto
        </x-dropdown-link>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Projeto
                                </th>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Dono
                                </th>
                                <th
                                    class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projetos as $projeto)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <div class="flex items-center">
                                            <div class="ml-3">
                                                <p class="text-gray-900 whitespace-no-wrap font-bold">{{ $projeto->nome }}
                                                </p>
                                                <p class="text-gray-500 text-xs truncate w-48">
                                                    {{ Str::limit($projeto->descricao, 40) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <span
                                            class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                            <span aria-hidden
                                                class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                            <span class="relative text-xs">{{ $projeto->status }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        {{ $projeto->owner->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                                        <div class="flex item-center justify-center space-x-2">
                                            <a href="{{ route('treetask.show', $projeto->id_projeto) }}"
                                                class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded text-xs font-bold border border-blue-200"
                                                title="Ver Quadro Kanban">
                                                Kanban 📋
                                            </a>

                                            <a href="{{ route('treetask.tree.view', $projeto->id_projeto) }}"
                                                class="text-purple-600 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 px-3 py-1 rounded text-xs font-bold border border-purple-200"
                                                title="Ver Árvore Hierárquica">
                                                Árvore 🌳
                                            </a>

                                            <a href="{{ route('treetask.ai.index', ['type' => 'project', 'id' => $projeto->id_projeto]) }}"
                                                class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded text-xs font-bold border border-indigo-200 flex items-center"
                                                title="Comando IA para este projeto">
                                                IA ✨
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-5 text-center text-gray-500">Nenhum projeto encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-TreeTask::layout>