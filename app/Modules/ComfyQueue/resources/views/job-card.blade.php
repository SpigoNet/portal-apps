<div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow" data-job-id="{{ $job->id }}">
    {{-- Preview Media --}}
    <div class="relative bg-gray-200 dark:bg-gray-700 aspect-video overflow-hidden flex items-center justify-center">
        @php
            $mediaFile = null;
            if ($job->output_files && count($job->output_files) > 0) {
                foreach ($job->output_files as $file) {
                    if (is_array($file) && !empty($file['url'])) {
                        $url = $file['url'];
                        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $mediaFile = ['type' => 'image', 'url' => $url];
                            break;
                        } elseif (in_array($ext, ['mp4', 'webm', 'mov', 'avi'])) {
                            $mediaFile = ['type' => 'video', 'url' => $url];
                            break;
                        } elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'])) {
                            $mediaFile = ['type' => 'audio', 'url' => $url];
                            break;
                        }
                    }
                }
            }
        @endphp

        @if ($mediaFile && $mediaFile['type'] === 'image')
            <img src="{{ $mediaFile['url'] }}" alt="Job {{ $job->id }}" class="w-full h-full object-cover">
        @elseif ($mediaFile && $mediaFile['type'] === 'video')
            <video src="{{ $mediaFile['url'] }}" class="w-full h-full object-cover" controls></video>
        @elseif ($mediaFile && $mediaFile['type'] === 'audio')
            <div class="flex flex-col items-center justif-center w-full h-full">
                <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M18 3H2c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14l4 4V5c0-1.1-.9-2-2-2zm-2 8h-4v4h-2v-4H6V9h4V5h2v4h4v2z" />
                </svg>
                <audio src="{{ $mediaFile['url'] }}" controls class="mt-2 w-4/5"></audio>
            </div>
        @else
            <div class="flex items-center justify-center w-full h-full">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        @endif

        {{-- Status Badge --}}
        <div class="absolute top-2 right-2">
            <span class="px-2 py-1 rounded text-xs font-medium
                @if($job->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300
                @elseif($job->status === 'processing') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                @elseif($job->status === 'done') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                @elseif($job->status === 'error') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                @endif">
                {{ ucfirst($job->status) }}
            </span>
        </div>

        {{-- Multiple Files Badge --}}
        @if ($job->output_files && count($job->output_files) > 1)
            <div class="absolute bottom-2 left-2 bg-black/60 text-white px-2 py-1 rounded text-xs">
                {{ count($job->output_files) }} arquivo(s)
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="p-4">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Job #{{ $job->id }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $job->type }}</p>
            </div>
        </div>

        <div class="space-y-2 text-sm mb-4">
            @if ($job->required_models && count($job->required_models) > 0)
                <div class="text-gray-600 dark:text-gray-400">
                    <span class="font-medium">{{ count($job->required_models) }} modelo(s)</span>
                </div>
            @endif

            @if ($job->error)
                <div class="text-red-600 dark:text-red-400 truncate" title="{{ $job->error }}">
                    <span class="font-medium">Erro:</span> {{ Str::limit($job->error, 50) }}
                </div>
            @endif

            @php($latestLog = $job->latestLogEntry())
            @if ($latestLog)
                <div class="text-gray-600 dark:text-gray-400 truncate" title="{{ $latestLog['message'] }}">
                    {{ Str::limit($latestLog['message'], 60) }}
                </div>
            @endif

            <div class="text-xs text-gray-500 dark:text-gray-500">
                {{ $job->created_at?->format('d/m H:i') }}
                @if ($job->finished_at)
                    - {{ $job->finished_at?->format('H:i') }}
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('comfy-queue.edit', $job->id) }}" class="px-2 py-1 rounded border text-xs hover:bg-gray-100 dark:hover:bg-gray-700 transition">Editar</a>

            <form action="{{ route('comfy-queue.duplicate', $job->id) }}" method="POST" class="inline" onsubmit="return confirm('Duplicar job #{{ $job->id }}?')">
                @csrf
                <button type="submit" class="px-2 py-1 rounded border text-xs text-indigo-700 border-indigo-300 hover:bg-indigo-50 dark:text-indigo-300 dark:border-indigo-700 dark:hover:bg-indigo-900/20 transition">Duplicar</button>
            </form>

            <form action="{{ route('comfy-queue.destroy', $job->id) }}" method="POST" class="inline" onsubmit="return confirm('Excluir job #{{ $job->id }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-2 py-1 rounded border text-xs text-red-700 border-red-300 hover:bg-red-50 dark:text-red-300 dark:border-red-700 dark:hover:bg-red-900/20 transition">Excluir</button>
            </form>

            @if ($job->status === 'error' || $job->status === 'pending')
                <form action="{{ route('comfy-queue.requeue', $job->id) }}" method="POST" class="inline" onsubmit="return confirm('Reenfileirar job #{{ $job->id }}?')">
                    @csrf
                    <button type="submit" class="px-2 py-1 rounded border text-xs text-blue-700 border-blue-300 hover:bg-blue-50 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-900/20 transition">Reenfileirar</button>
                </form>
            @endif
        </div>
    </div>
</div>
