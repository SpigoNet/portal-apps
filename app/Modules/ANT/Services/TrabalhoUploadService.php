<?php

namespace App\Modules\ANT\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TrabalhoUploadService
{
    /**
     * Faz upload de arquivo com tratamento de erro melhorado
     * 
     * @param \Illuminate\Http\UploadedFile $arquivo
     * @param string $targetPath
     * @return string|null - Path do arquivo ou null em caso de erro
     * @throws \Exception
     */
    public static function uploadArquivo($arquivo, $targetPath)
    {
        $fileName = $arquivo->getClientOriginalName();
        
        Log::info('Starting file upload', [
            'file_name' => $fileName,
            'target_path' => $targetPath,
            'file_size' => $arquivo->getSize(),
        ]);
        
        try {
            // Teste de conectividade antes de processar
            self::testSftpConnection();
            
            // Cria diretório se não existir
            if (!Storage::disk('sftp')->exists($targetPath)) {
                Storage::disk('sftp')->makeDirectory($targetPath);
                Log::info('Directory created on SFTP', ['path' => $targetPath]);
            }
            
            // Faz upload
            $path = $arquivo->storeAs(
                $targetPath,
                $fileName,
                'sftp'
            );
            
            // Verifica se arquivo foi salvo
            if (!Storage::disk('sftp')->exists($path)) {
                throw new \Exception("Arquivo não foi encontrado após upload em $path");
            }
            
            Log::info('File uploaded successfully', [
                'file' => $fileName,
                'path' => $path,
                'size' => Storage::disk('sftp')->size($path) ?? 'unknown'
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'file' => $fileName,
                'target_path' => $targetPath,
                'error' => $e->getMessage(),
                'exception_type' => class_basename($e),
                'file_size' => $arquivo->getSize(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Testa conectividade SFTP
     * 
     * @throws \Exception
     */
    public static function testSftpConnection()
    {
        try {
            Storage::disk('sftp')->exists('/');
        } catch (\Exception $e) {
            Log::error('SFTP connection test failed', [
                'host' => env('SFTP_HOST'),
                'port' => env('SFTP_PORT'),
                'username' => env('SFTP_USERNAME'),
                'error' => $e->getMessage(),
                'exception_type' => class_basename($e),
            ]);
            
            throw new \Exception(
                'Não foi possível conectar ao serviço de armazenamento. ' .
                'Verifique as credenciais SFTP em .env e tente novamente.',
                0,
                $e
            );
        }
    }
    
    /**
     * Retorna informações de diagnóstico
     */
    public static function getDiagnosticInfo()
    {
        return [
            'sftp_host' => env('SFTP_HOST'),
            'sftp_port' => env('SFTP_PORT'),
            'sftp_username' => env('SFTP_USERNAME'),
            'sftp_root' => env('SFTP_ROOT'),
            'cdn_url' => env('CDN_URL'),
            'connection_status' => 'unknown',
        ];
    }
}
