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
            // Cria diretório se não existir
            if (!Storage::disk('public')->exists($targetPath)) {
                Storage::disk('public')->makeDirectory($targetPath);
                Log::info('Directory created', ['path' => $targetPath]);
            }
            
            // Faz upload
            $path = $arquivo->storeAs(
                $targetPath,
                $fileName,
                'public'
            );
            
            // Verifica se arquivo foi salvo
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception("Arquivo não foi encontrado após upload em $path");
            }
            
            Log::info('File uploaded successfully', [
                'file' => $fileName,
                'path' => $path,
                'size' => Storage::disk('public')->size($path) ?? 'unknown'
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
}
