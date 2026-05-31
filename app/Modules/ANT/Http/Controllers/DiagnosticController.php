<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DiagnosticController extends Controller
{
    /**
     * Exibe página de diagnóstico de armazenamento
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

        // Teste: Verificar disco public
        $results['tests']['storage'] = $this->testStorage();

        // Informações do Sistema
        $results['system'] = $this->getSystemInfo();

        return $results;
    }

    /**
     * Testar disco public
     */
    private function testStorage()
    {
        try {
            $disk = Storage::disk('public');
            $testPath = '_diag_test_' . now()->timestamp;
            $disk->put($testPath, 'ok');
            $exists = $disk->exists($testPath);
            $disk->delete($testPath);

            if ($exists) {
                return [
                    'name' => 'Disco Public (Laravel)',
                    'status' => 'success',
                    'message' => '✓ Disco public está operacional',
                    'details' => 'Leitura e escrita funcionando corretamente'
                ];
            }

            return [
                'name' => 'Disco Public (Laravel)',
                'status' => 'error',
                'message' => '✗ Falha ao escrever no disco public',
                'details' => 'Verifique permissões do diretório storage/app/public'
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Disco Public (Laravel)',
                'status' => 'error',
                'message' => '✗ Erro ao acessar disco public',
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
            'disk' => 'public',
            'storage_path' => storage_path('app/public'),
        ];
    }
}
