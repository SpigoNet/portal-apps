<?php

namespace App\Modules\MundosDeMim\Console\Commands;

use App\Modules\MundosDeMim\Services\DailyPhotoService;
use Illuminate\Console\Command;

class ProcessDailyPhotos extends Command
{
    protected $signature = 'mundos-de-mim:process-daily-photos {--dry-run : Simular sem enviar}';

    protected $description = 'Gera e envia a foto do dia para todos os usuários ativos';

    public function handle(): int
    {
        $this->info('Iniciando processamento das fotos do dia...');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo simulação ativado - nenhuma mensagem será enviada');
        }

        $service = new DailyPhotoService;
        $result = $service->processAll();

        $this->info("Total de usuários: {$result['total']}");
        $this->info("Processados com sucesso: {$result['processed']}");
        $this->info("Falhas: {$result['failed']}");

        if ($result['failed'] > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
