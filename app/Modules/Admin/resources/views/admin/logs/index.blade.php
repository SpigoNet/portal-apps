<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .log-line { white-space: pre-wrap; word-break: break-all; }
        .log-error { color: #dc2626; background: #fef2f2; }
        .log-warning { color: #d97706; background: #fffbeb; }
        .log-info { color: #2563eb; background: #eff6ff; }
        .log-debug { color: #7c3aed; background: #f5f3ff; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-gray-700 mr-4">← Dashboard</a>
                        <h1 class="text-xl font-semibold text-gray-800">Log Viewer</h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshLog()" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                            🔄 Atualizar
                        </button>
                        <a href="{{ route('admin.logs.download') }}" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                            ⬇ Download
                        </a>
                        <form action="{{ route('admin.logs.clear') }}" method="POST" onsubmit="return confirm('Tem certeza que deseja limpar o log?');">
                            @csrf
                            <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                                🗑 Limpar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if($error)
                <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded">
                    {{ $error }}
                </div>
            @else
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                        <span class="text-sm text-gray-600">Últimas 500 linhas</span>
                        <span class="text-xs text-gray-400" id="lastUpdate">Atualizado automaticamente a cada 30s</span>
                    </div>
                    <div class="overflow-x-auto max-h-screen">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-48">Data/Hora</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Nível</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensagem</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($logs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $log['timestamp'] }}</td>
                                        <td class="px-4 py-2">
                                            @php
                                                $levelClass = match($log['level']) {
                                                    'ERROR', 'CRITICAL' => 'log-error',
                                                    'WARNING', 'ALERT' => 'log-warning',
                                                    'INFO' => 'log-info',
                                                    'DEBUG' => 'log-debug',
                                                    default => ''
                                                };
                                            @endphp
                                            <span class="px-2 py-1 text-xs font-semibold rounded {{ $levelClass }}">
                                                {{ $log['level'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 log-line">{{ $log['message'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">Nenhum log encontrado</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        let refreshInterval;

        function refreshLog() {
            window.location.reload();
        }

        // Auto refresh every 30 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const lastUpdate = document.getElementById('lastUpdate');
            let seconds = 30;
            
            refreshInterval = setInterval(() => {
                seconds--;
                if (seconds <= 0) {
                    refreshLog();
                } else if (lastUpdate) {
                    lastUpdate.textContent = `Atualizando em ${seconds}s...`;
                }
            }, 1000);
        });
    </script>
</body>
</html>
