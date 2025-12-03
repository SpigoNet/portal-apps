<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Painel de Métricas do Portal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-bold mb-4">Uso por Módulo</h3>
                    <table class="min-w-full text-left text-sm font-light">
                        <thead class="border-b font-medium dark:border-neutral-500">
                        <tr>
                            <th scope="col" class="px-6 py-4">Nome do Módulo</th>
                            <th scope="col" class="px-6 py-4">Total de Acessos</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($acessosPorModulo as $item)
                            <tr class="border-b dark:border-neutral-500">
                                <td class="whitespace-nowrap px-6 py-4">{{ $item->modulo_nome }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $item->total }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center">Nenhum dado registrado ainda.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-bold mb-4">Top 10 Usuários Mais Ativos</h3>
                    <table class="min-w-full text-left text-sm font-light">
                        <thead class="border-b font-medium dark:border-neutral-500">
                        <tr>
                            <th scope="col" class="px-6 py-4">Usuário</th>
                            <th scope="col" class="px-6 py-4">Email</th>
                            <th scope="col" class="px-6 py-4">Acessos Totais</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($usuariosAtivos as $ativo)
                            <tr class="border-b dark:border-neutral-500">
                                <td class="whitespace-nowrap px-6 py-4">{{ $ativo->user->name ?? 'Usuário Removido' }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $ativo->user->email ?? '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $ativo->total }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-bold mb-4">Últimos 50 Acessos</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm font-light">
                            <thead class="border-b font-medium dark:border-neutral-500">
                            <tr>
                                <th scope="col" class="px-6 py-4">Data/Hora</th>
                                <th scope="col" class="px-6 py-4">Usuário</th>
                                <th scope="col" class="px-6 py-4">Módulo</th>
                                <th scope="col" class="px-6 py-4">URL</th>
                                <th scope="col" class="px-6 py-4">Método</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($ultimosAcessos as $acesso)
                                <tr class="border-b dark:border-neutral-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="whitespace-nowrap px-6 py-4">{{ $acesso->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $acesso->user->name ?? 'Anônimo' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 font-bold">{{ $acesso->modulo_nome }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ Str::limit($acesso->url_acessada, 50) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $acesso->metodo_http }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
