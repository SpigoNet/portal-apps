<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestSftpConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sftp:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa conectividade SFTP e diagnóstico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Teste de Conectividade SFTP ===');
        $this->newLine();

        // Exibe configuração
        $this->info('Configuração SFTP:');
        $this->line("  Host: " . env('SFTP_HOST', 'NÃO CONFIGURADO'));
        $this->line("  Port: " . env('SFTP_PORT', '2222'));
        $this->line("  Username: " . env('SFTP_USERNAME', 'NÃO CONFIGURADO'));
        $this->line("  Root: " . env('SFTP_ROOT', '/data/uploads'));
        $this->newLine();

        // Tenta conectar
        $this->info('Testando conexão...');
        
        try {
            $disk = Storage::disk('sftp');
            
            // Teste 1: Verificar se raiz existe
            $rootExists = $disk->exists('/');
            $this->info('✓ Conexão SSH estabelecida com sucesso');
            $this->line("  Raiz acessível: " . ($rootExists ? 'SIM' : 'NÃO'));
            
            // Teste 2: Listar conteúdo raiz
            try {
                $contents = $disk->listContents('/');
                $this->info("✓ Conteúdo raiz listado ({$contents->count()} itens)");
            } catch (\Exception $e) {
                $this->warn("✗ Erro ao listar raiz: {$e->getMessage()}");
            }
            
            // Teste 3: Testar caminho ant/entregas
            $antPath = 'ant/entregas';
            $antExists = $disk->exists($antPath);
            $this->info("Caminho ANT ($antPath): " . ($antExists ? 'EXISTS' : 'NÃO EXISTE'));
            
            $this->newLine();
            $this->info('=== Teste Concluído com Sucesso ===');
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('✗ FALHA na Conexão SFTP');
            $this->newLine();
            $this->error("Erro: {$e->getMessage()}");
            $this->line("Tipo: " . class_basename($e));
            $this->newLine();
            $this->warn('Sugestões:');
            $this->line('  1. Verifique se as credenciais SFTP estão corretas em .env');
            $this->line('  2. Verifique se o host ' . env('SFTP_HOST') . ' está acessível');
            $this->line('  3. Verifique a porta ' . env('SFTP_PORT') . ' está aberta no firewall');
            $this->line('  4. Verifique se o usuário ' . env('SFTP_USERNAME') . ' tem permissão de acesso');
            
            return self::FAILURE;
        }
    }
}
