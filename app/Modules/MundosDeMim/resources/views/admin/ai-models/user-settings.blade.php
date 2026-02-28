<x-MundosDeMim::layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Configurações de Modelo por Usuário</h2></x-slot>

    <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between mb-4"><a href="{{ route('mundos-de-mim.admin.ai-models.index') }}" class="text-indigo-600 hover:text-indigo-900">&larr; Voltar para Modelos</a></div>
        @if(session('success'))<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>@endif

        <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Modelo Padrão Global</h3>
            <form action="{{ route('mundos-de-mim.admin.ai-models.update-global-default') }}" method="POST" class="flex gap-4 items-end">@csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Modelo padrão para usuários sem configuração individual</label>
                    <select name="default_provider_id" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach($models as $model)
                            <option value="{{ $model->id }}" {{ $model->is_default ? 'selected' : '' }}>{{ $model->gatewayProvider?->name ?? 'Sem Provedor' }} / {{ $model->name }} ({{ $model->model }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Atualizar Padrão</button>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configuração Individual</h3>
                <p class="text-sm text-gray-500 mb-4">Se não houver configuração individual, será usado o modelo padrão global.</p>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modelo Atual</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ação</th>
                </tr></thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $user->mundosDeMimAiSetting?->aiProvider?->name ?? ($user->mundosDeMimDefaultAiProvider?->name ? 'Padrão: '.$user->mundosDeMimDefaultAiProvider->name : 'Padrão Global') }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('mundos-de-mim.admin.ai-models.update-user-settings') }}" method="POST" class="flex gap-2">@csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <select name="ai_provider_id" class="rounded-md border-gray-300 text-sm">
                                        <option value="">Padrão Global</option>
                                        @foreach($models as $model)
                                            <option value="{{ $model->id }}" {{ $user->mundosDeMimAiSetting && $user->mundosDeMimAiSetting->ai_provider_id == $model->id ? 'selected' : '' }}>{{ $model->gatewayProvider?->name ?? 'Sem Provedor' }} / {{ $model->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="bg-indigo-600 text-white px-3 py-1 rounded text-sm hover:bg-indigo-700">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum usuário encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</x-MundosDeMim::layout>
