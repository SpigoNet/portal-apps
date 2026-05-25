<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DiagnosticController extends Controller
{
    /**
     * Exibe página de diagnóstico SFTP
     */
    public function index()
    {
        $diagnostics = $this->runDiagnostics();
        
        return view('ANT::diagnostic', compact('diagnostics'));
    }

    /**
     * Executa testes de diagnóstico
     */
    private function runDiagnostics()
    {
        $results = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'tests' => []
        ];

        // Teste 1: Verificar Configuração
        $results['tests']['configuration'] = $this->testConfiguration();

        // Teste 2: Resolver DNS
        $results['tests']['dns'] = $this->testDNS();

        // Teste 3: Testar Conectividade de Porta
        $results['tests']['port'] = $this->testPort();

        // Teste 4: Testar SSH
        $results['tests']['ssh'] = $this->testSSH();

        // Teste 5: Testar Filesystem SFTP
        $results['tests']['filesystem'] = $this->testFilesystem();

        // Teste 6: Informações do Sistema
        $results['system'] = $this->getSystemInfo();

        return $results;
    }

    /**
     * Teste 1: Verificar Configuração
     */
    private function testConfiguration()
    {
        return [
            'name' => 'Configuração SFTP',
            'status' => 'info',
            'checks' => [
                'SFTP_HOST' => env('SFTP_HOST') ? '✓ ' . env('SFTP_HOST') : '✗ Não configurado',
                'SFTP_PORT' => env('SFTP_PORT') ? '✓ ' . env('SFTP_PORT') : '✗ Não configurado',
                'SFTP_USERNAME' => env('SFTP_USERNAME') ? '✓ ' . env('SFTP_USERNAME') : '✗ Não configurado',
                'SFTP_PASSWORD' => env('SFTP_PASSWORD') ? '✓ Configurado' : '✗ Não configurado',
                'SFTP_ROOT' => env('SFTP_ROOT') ? '✓ ' . env('SFTP_ROOT') : '✗ Não configurado',
            ]
        ];
    }

    /**
     * Teste 2: Resolver DNS
     */
    private function testDNS()
    {
        $host = env('SFTP_HOST');
        
        try {
            $ip = gethostbyname($host);
            
            if ($ip === $host) {
                return [
                    'name' => 'Resolução DNS',
                    'status' => 'error',
                    'message' => "❌ Não conseguiu resolver '$host'",
                    'details' => "DNS não está resolvendo o hostname"
                ];
            }

            return [
                'name' => 'Resolução DNS',
                'status' => 'success',
                'message' => "✓ Resolveu '$host' para '$ip'",
                'details' => "DNS está funcionando corretamente"
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Resolução DNS',
                'status' => 'error',
                'message' => "❌ Erro ao resolver DNS",
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Teste 3: Testar Conectividade de Porta
     */
    private function testPort()
    {
        $host = env('SFTP_HOST');
        $port = env('SFTP_PORT', 2222);

        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if ($socket) {
            fclose($socket);
            return [
                'name' => 'Conectividade da Porta',
                'status' => 'success',
                'message' => "✓ Porta $port está respondendo",
                'details' => "Conexão bem-sucedida em $host:$port"
            ];
        }

        $errorMessages = [
            111 => "Connection refused (111) - Porta não está aberta ou serviço não está respondendo",
            110 => "Connection timed out (110) - Host não responde ou firewall bloqueando",
            -1 => "DNS lookup failed - Hostname não conseguiu ser resolvido",
        ];

        $errorMsg = $errorMessages[$errno] ?? "Erro desconhecido ($errno)";

        return [
            'name' => 'Conectividade da Porta',
            'status' => 'error',
            'message' => "❌ Não conseguiu conectar em $host:$port",
            'details' => "$errstr ($errno) - $errorMsg"
        ];
    }

    /**
     * Teste 4: Testar SSH
     */
    private function testSSH()
    {
        $host = env('SFTP_HOST');
        $port = env('SFTP_PORT', 2222);
        $username = env('SFTP_USERNAME');
        $password = env('SFTP_PASSWORD');

        try {
            // Apenas teste de conectividade, não autenticação completa
            $socket = @fsockopen($host, $port, $errno, $errstr, 5);

            if (!$socket) {
                return [
                    'name' => 'Teste SSH/SFTP',
                    'status' => 'error',
                    'message' => "❌ Não conseguiu conectar ao SSH",
                    'details' => "Resolva o problema de porta primeiro"
                ];
            }

            // Ler banner SSH
            $banner = fgets($socket, 1024);
            fclose($socket);

            if (strpos($banner, 'SSH') !== false) {
                return [
                    'name' => 'Teste SSH/SFTP',
                    'status' => 'success',
                    'message' => "✓ SSH está respondendo",
                    'details' => trim($banner)
                ];
            }

            return [
                'name' => 'Teste SSH/SFTP',
                'status' => 'warning',
                'message' => "⚠ Conexão estabelecida mas SSH não confirmado",
                'details' => "Resposta recebida: " . trim($banner)
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Teste SSH/SFTP',
                'status' => 'error',
                'message' => "❌ Erro ao conectar SSH",
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Teste 5: Testar Filesystem SFTP
     */
    private function testFilesystem()
    {
        try {
            Storage::disk('sftp')->exists('/');

            return [
                'name' => 'Filesystem SFTP (Laravel)',
                'status' => 'success',
                'message' => "✓ Conseguiu conectar e autenticar via SFTP",
                'details' => "Acesso bem-sucedido ao servidor SFTP"
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Filesystem SFTP (Laravel)',
                'status' => 'error',
                'message' => "❌ Erro ao conectar SFTP via Laravel",
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Informações do Sistema
     */
    private function getSystemInfo()
    {
        return [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'client_ip' => $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        ];
    }
}
