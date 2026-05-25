@extends('layouts.app')

@section('title', 'Diagnóstico SFTP - ANT')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">🔍 Diagnóstico SFTP - ANT</h1>
            <p class="text-gray-600">Teste de conectividade para upload de trabalhos</p>
            <p class="text-sm text-gray-500 mt-2">Última atualização: {{ $diagnostics['timestamp'] }}</p>
        </div>

        <!-- Testes -->
        <div class="space-y-4 mb-8">
            @foreach ($diagnostics['tests'] as $testKey => $test)
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="flex items-center px-6 py-4 border-l-4 
                        @if($test['status'] === 'success') border-green-500 bg-green-50
                        @elseif($test['status'] === 'error') border-red-500 bg-red-50
                        @elseif($test['status'] === 'warning') border-yellow-500 bg-yellow-50
                        @else border-blue-500 bg-blue-50
                        @endif
                    ">
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg text-gray-800">
                                @if($test['status'] === 'success')
                                    ✅
                                @elseif($test['status'] === 'error')
                                    ❌
                                @elseif($test['status'] === 'warning')
                                    ⚠️
                                @else
                                    ℹ️
                                @endif
                                {{ $test['name'] }}
                            </h3>
                            
                            @if (isset($test['message']))
                                <p class="text-gray-700 mt-1">{{ $test['message'] }}</p>
                            @endif
                            
                            @if (isset($test['details']))
                                <p class="text-sm text-gray-600 mt-2">{{ $test['details'] }}</p>
                            @endif

                            @if (isset($test['checks']))
                                <div class="mt-3 space-y-1">
                                    @foreach ($test['checks'] as $checkName => $checkValue)
                                        <div class="text-sm text-gray-700">
                                            <strong>{{ $checkName }}:</strong> {{ $checkValue }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Resumo do Sistema -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">📊 Informações do Sistema</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">PHP Version</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $diagnostics['system']['php_version'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Laravel Version</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $diagnostics['system']['laravel_version'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Server IP</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $diagnostics['system']['server_ip'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Client IP</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $diagnostics['system']['client_ip'] }}</p>
                </div>
            </div>
        </div>

        <!-- Instruções de Resolução -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <h2 class="text-lg font-bold text-blue-900 mb-3">💡 O que fazer?</h2>
            
            <div class="space-y-3">
                @if ($diagnostics['tests']['dns']['status'] === 'error')
                    <div class="bg-white p-3 rounded border-l-4 border-red-500">
                        <p class="font-semibold text-gray-800">❌ DNS não está resolvendo</p>
                        <p class="text-sm text-gray-600 mt-1">Configure seu DNS para apontar <code class="bg-gray-100 px-2 py-1 rounded">files.spigo.net</code> para seu IP público.</p>
                    </div>
                @endif

                @if ($diagnostics['tests']['port']['status'] === 'error')
                    <div class="bg-white p-3 rounded border-l-4 border-red-500">
                        <p class="font-semibold text-gray-800">❌ Porta 2222 não está respondendo</p>
                        <p class="text-sm text-gray-600 mt-1">Verifique:</p>
                        <ul class="text-sm text-gray-600 mt-2 list-disc list-inside">
                            <li>Container Docker está rodando? <code class="bg-gray-100 px-2 py-1 rounded text-xs">docker ps | grep cdn_uploader</code></li>
                            <li>Port-forwarding está ativo? <code class="bg-gray-100 px-2 py-1 rounded text-xs">docker port cdn_uploader</code></li>
                            <li>Firewall está permitindo porta 2222?</li>
                        </ul>
                    </div>
                @endif

                @if ($diagnostics['tests']['filesystem']['status'] === 'error')
                    <div class="bg-white p-3 rounded border-l-4 border-red-500">
                        <p class="font-semibold text-gray-800">❌ Erro ao conectar ao SFTP</p>
                        <p class="text-sm text-gray-600 mt-1">Verifique as credenciais em <code class="bg-gray-100 px-2 py-1 rounded">.env</code>:</p>
                        <ul class="text-sm text-gray-600 mt-2 list-disc list-inside">
                            <li><code class="bg-gray-100 px-2 py-1 rounded text-xs">SFTP_USERNAME=cdnuser</code></li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded text-xs">SFTP_PASSWORD=GuMa1726</code></li>
                        </ul>
                    </div>
                @endif

                @if ($diagnostics['tests']['dns']['status'] === 'success' && 
                     $diagnostics['tests']['port']['status'] === 'success' && 
                     $diagnostics['tests']['filesystem']['status'] === 'success')
                    <div class="bg-white p-3 rounded border-l-4 border-green-500">
                        <p class="font-semibold text-gray-800">✅ Tudo está funcionando!</p>
                        <p class="text-sm text-gray-600 mt-1">Você já pode enviar trabalhos via SFTP.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="flex gap-3">
            <a href="{{ route('ant.home') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                ← Voltar para Trabalhos
            </a>
            <button onclick="location.reload()" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                🔄 Atualizar Teste
            </button>
        </div>
    </div>
</div>

<style>
    code {
        font-family: 'Monaco', 'Courier New', monospace;
    }
</style>
@endsection
