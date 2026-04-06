<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="flex justify-between items-center mb-6 px-4 sm:px-0">
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Gerenciador ComfyUI') }}
                </h2>
                <a href="{{ route('comfy-queue.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Novo Job</a>
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
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400">Nenhum job na fila.</td>
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
