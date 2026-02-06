<?php

namespace App\Modules\MundosDeMim\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RechargeCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mundos-de-mim:recharge-credits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recarrega 5 créditos semanais para assinantes Eco e Prime.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando recarga semanal de créditos...');

        $updatedCount = User::whereIn('subscription_plan', ['eco', 'prime'])
            ->increment('credits', 5);

        Log::info("MundosDeMim: Recarga semanal concluída para {$updatedCount} usuários.");
        $this->info("Sucesso! {$updatedCount} usuários receberam +5 créditos.");
    }
}
