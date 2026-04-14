<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="flex justify-between items-center mb-6 px-4 sm:px-0">
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Gerenciador ComfyUI') }}
                </h2>
                <div class="flex flex-col items-end gap-2">
                    <a href="{{ route('comfy-queue.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Novo Job</a>
                    <a href="{{ route('comfy-queue.assistant') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm hover:bg-gray-700">Assistente de Criação</a>
                </div>
            </div>

            <div class="mb-4 px-4 sm:px-0">
                <form method="GET" action="{{ route('comfy-queue.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-6">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por ID, tipo, prompt_id ou erro"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm"
                        >
                    </div>
                    <div class="md:col-span-3">
                        <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm">
                            <option value="">Todos os status</option>
                            @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'done' => 'Done', 'error' => 'Error'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3 flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-black">Filtrar</button>
                        <a href="{{ route('comfy-queue.index') }}" class="px-4 py-2 border rounded-md text-sm text-gray-600 dark:text-gray-300">Limpar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    @if(session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">ID</th>
                                <th class="border-b py-2 px-4">Tipo</th>
                                <th class="border-b py-2 px-4">Status</th>
                                <th class="border-b py-2 px-4">Modelos</th>
                                <th class="border-b py-2 px-4">Resultado</th>
                                <th class="border-b py-2 px-4">Último log</th>
                                <th class="border-b py-2 px-4">Criado em</th>
                                <th class="border-b py-2 px-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobs as $job)
                            @php($latestLog = $job->latestLogEntry())
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="border-b py-2 px-4 text-sm">{{ $job->id }}</td>
                                <td class="border-b py-2 px-4 text-sm font-mono">{{ $job->type }}</td>
                                <td class="border-b py-2 px-4">
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        @if($job->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300
                                        @elseif($job->status === 'processing') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                                        @elseif($job->status === 'done') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                        @elseif($job->status === 'error') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                        @endif">
                                        {{ ucfirst($job->status) }}
                                    </span>
                                </td>
                                <td class="border-b py-2 px-4 text-xs text-gray-600 dark:text-gray-300">
                                    @if($job->required_models && count($job->required_models) > 0)
                                        <span title="{{ collect($job->required_models)->pluck('name')->implode(', ') }}">
                                            {{ count($job->required_models) }} modelo(s)
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="border-b py-2 px-4 text-xs text-gray-600 dark:text-gray-300">
                                    @if($job->output_files && count($job->output_files) > 0)
                                        {{ count($job->output_files) }} arquivo(s)
                                        @php($linkedOutputs = collect($job->output_files)->filter(fn ($file) => is_array($file) && !empty($file['url']))->take(2))
                                        @if($linkedOutputs->isNotEmpty())
                                            <div class="mt-1 space-x-2">
                                                @foreach($linkedOutputs as $index => $file)
                                                    <a href="{{ $file['url'] }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">Arquivo {{ $index + 1 }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    @elseif($job->error)
                                        <span class="text-red-500" title="{{ $job->error }}">Erro</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="border-b py-2 px-4 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    @if($latestLog)
                                        <span title="{{ $latestLog['message'] }}">{{ Str::limit($latestLog['message'], 60) }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="border-b py-2 px-4 text-sm text-gray-500">{{ $job->created_at->format('d/m H:i') }}</td>
                                <td class="border-b py-2 px-4">
                                    <div class="flex justify-end gap-2 flex-wrap">
                                        <a href="{{ route('comfy-queue.edit', $job->id) }}" class="px-2 py-1 rounded border text-xs hover:bg-gray-100 dark:hover:bg-gray-700">Editar</a>

                                        <form action="{{ route('comfy-queue.requeue', $job->id) }}" method="POST" onsubmit="return confirm('Reenfileirar job #{{ $job->id }}?')">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 rounded border text-xs text-blue-700 border-blue-300 hover:bg-blue-50 dark:text-blue-300 dark:border-blue-700">Reenfileirar</button>
                                        </form>

                                        <form action="{{ route('comfy-queue.duplicate', $job->id) }}" method="POST" onsubmit="return confirm('Duplicar job #{{ $job->id }}?')">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 rounded border text-xs text-indigo-700 border-indigo-300 hover:bg-indigo-50 dark:text-indigo-300 dark:border-indigo-700">Duplicar</button>
                                        </form>

                                        <form action="{{ route('comfy-queue.destroy', $job->id) }}" method="POST" onsubmit="return confirm('Excluir job #{{ $job->id }}? Esta ação não pode ser desfeita.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded border text-xs text-red-700 border-red-300 hover:bg-red-50 dark:text-red-300 dark:border-red-700">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-400">Nenhum job encontrado.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $jobs->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
