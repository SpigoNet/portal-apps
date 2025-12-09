<x-app-layout>
        <x-slot name="moduleName">Mithril</x-slot>
        <x-slot name="moduleHomeRoute">mithril.index</x-slot>

        <x-slot name="moduleMenu">
            <x-dropdown-link :href="route('mithril.index')">
                Dashboard
            </x-dropdown-link>
            <x-dropdown-link :href="route('mithril.lancamentos.index')">
                Lançamentos
            </x-dropdown-link>
            <x-dropdown-link :href="route('mithril.pre-transacoes.index')">
                Assinaturas
            </x-dropdown-link>
            <x-dropdown-link :href="route('mithril.fechamentos.index')">
                Fechar Mês
            </x-dropdown-link>
            <div class="border-t border-gray-100 dark:border-gray-600"></div>
            <x-dropdown-link :href="route('mithril.pre-transacoes.create')" class="text-blue-500">
                + Nova Transação
            </x-dropdown-link>
        </x-slot>
        <x-slot name="header">
        </x-slot>


    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lançamentos de {{ ucfirst($dataTitulo) }}
        </h2>
    </x-slot>

    <style>
        .valor-positivo { color: #155724; font-weight: bold; }
        .valor-negativo { color: #721c24; font-weight: bold; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <form method="GET" action="{{ route('mithril.lancamentos.index') }}" class="mb-6 flex gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mês</label>
                            <select name="mes" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>
                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ano</label>
                            <input type="number" name="ano" value="{{ $ano }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Conta</label>
                            <select name="conta_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Todas as Contas</option>
                                @foreach($contas as $conta)
                                    <option value="{{ $conta->id }}" {{ $contaId == $conta->id ? 'selected' : '' }}>
                                        {{ $conta->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Filtrar
                        </button>
                    </form>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($transacoes as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $item->data_efetiva->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $item->conta->nome }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $item->descricao }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="{{ $item->valor >= 0 ? 'valor-positivo' : 'valor-negativo' }}">
                                            R$ {{ number_format($item->valor, 2, ',', '.') }}
                                        </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Nenhum lançamento encontrado para este período.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-right font-bold">Saldo do Período:</td>
                            <td class="px-6 py-3 text-right font-bold">
                                    <span class="{{ $saldoMes >= 0 ? 'valor-positivo' : 'valor-negativo' }}">
                                        R$ {{ number_format($saldoMes, 2, ',', '.') }}
                                    </span>
                            </td>
                        </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>

        <div class="fixed bottom-6 right-6">
            <a href="{{ route('mithril.transacao.create') }}" class="bg-amber-800 text-white p-4 rounded-full shadow-lg hover:bg-amber-900 flex items-center justify-center" title="Nova Transação">
                <span class="text-2xl">+</span>
            </a>
        </div>
    </div>
</x-app-layout>
