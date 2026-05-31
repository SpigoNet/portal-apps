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
    protected $signature = 'storage:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa armazenamento local e diagnóstico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Teste de Armazenamento ===');
        $this->newLine();

        $this->info('Discos configurados:');
        $this->line("  Default: " . config('filesystems.default'));
        $this->newLine();

        // Tenta acessar disco public
        $this->info('Testando disco public...');

        try {
            $disk = Storage::disk('public');

            $rootExists = $disk->exists('/');
            $this->info('✓ Disco public acessível');
            $this->line("  Raiz acessível: " . ($rootExists ? 'SIM' : 'NÃO'));

            $this->newLine();
            $this->info('=== Teste Concluído com Sucesso ===');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ FALHA no acesso ao disco public');
            $this->newLine();
            $this->error("Erro: {$e->getMessage()}");
            $this->line("Tipo: " . class_basename($e));
            $this->newLine();
            $this->warn('Sugestões:');
            $this->line('  1. Verifique se o diretório storage/app/public existe');
            $this->line('  2. Verifique as permissões do diretório');

            return self::FAILURE;
        }
    }
}
