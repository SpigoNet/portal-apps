<?php

namespace App\Modules\Alfred\Console\Commands;

use App\Modules\Alfred\Models\ConsumoAgua;
use App\Modules\Alfred\Models\Medicamento;
use App\Modules\Alfred\Models\UserProfile;
use App\Modules\TreeTask\Models\Tarefa;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RelatorioManha extends Command
{
    protected $signature = 'alfred:relatorio-manha';

    protected $description = 'Gera o Relatório da Manhã (Top 3 tarefas do TreeTask e alertas)';

    public function handle(): int
    {
        $users = UserProfile::query()->get();

        if ($users->isEmpty()) {
            $this->warn('Nenhum usuário encontrado.');

            return 0;
        }

        foreach ($users as $profile) {
            $user = $profile->user;

            if (! $user) {
                continue;
            }

            if ($profile->modo_dia_ruim) {
                $this->info("Modo Dia Ruim ativo para {$user->name}. Relatório não enviado.");
                $this->newLine();

                continue;
            }

            $this->info("Gerando Relatório da Manhã para {$user->name}...");
            $this->newLine();

            $this->line('╔══════════════════════════════════════╗');
            $this->line('║      📋 RELATÓRIO DA MANHÃ          ║');
            $this->line('║         '.Carbon::now()->format('d/m/Y').'           ║');
            $this->line('╚══════════════════════════════════════╝');
            $this->newLine();

            // 1. Alertas de Medicamentos
            $medicamentosBaixo = Medicamento::baixoEstoque()->doUsuario($user->id)->get();
            if ($medicamentosBaixo->count() > 0) {
                $this->warn('💊 MEDICAMENTOS COM ESTOQUE BAIXO:');
                foreach ($medicamentosBaixo as $med) {
                    $alerta = $med->estoque_atual <= 0 ? '🚨 URGENTE: ' : '⚠️  ';
                    $this->line("   {$alerta}{$med->nome}: {$med->estoque_atual} unidades");
                }
                $this->newLine();
            }

            // 2. Hidratação
            $progressoAgua = ConsumoAgua::progressoHoje($user->id);
            if ($progressoAgua['consumido'] == 0) {
                $this->info('💧 HIDRATAÇÃO:');
                $this->line('   Comece o dia bebendo água! Meta: '.$progressoAgua['meta'].'ml');
                $this->newLine();
            } elseif ($progressoAgua['percentual'] < 30) {
                $this->warn('💧 HIDRATAÇÃO:');
                $this->line("   {$progressoAgua['consumido']}ml consumidos. Você está abaixo da meta!");
                $this->newLine();
            }

            // 3. Top 3 Tarefas do TreeTask
            $tarefas = Tarefa::with('fase.projeto.responsavel')
                ->whereHas('fase.projeto', fn ($q) => $q->where('id_user_owner', $user->id))
                ->whereNotIn('status', ['Concluído'])
                ->orderByRaw("CASE prioridade WHEN 'Urgente' THEN 4 WHEN 'Alta' THEN 3 WHEN 'Média' THEN 2 WHEN 'Baixa' THEN 1 ELSE 0 END DESC")
                ->orderBy('data_vencimento', 'asc')
                ->take(3)
                ->get();

            $this->info('🎯 TOP 3 TAREFAS DO TREETASK:');
            $this->newLine();

            if ($tarefas->count() == 0) {
                $this->line('   🎉 Nenhuma tarefa urgente! Aproveite o dia!');
            } else {
                $prioridadeEmoji = ['Urgente' => '🔴', 'Alta' => '🟠', 'Média' => '🟡', 'Baixa' => '🔵'];

                foreach ($tarefas as $index => $tarefa) {
                    $numero = $index + 1;
                    $icone = $prioridadeEmoji[$tarefa->prioridade] ?? '⚪';
                    $this->line("   {$numero}. {$icone} {$tarefa->titulo}");
                    if ($tarefa->data_vencimento) {
                        $this->line("      Prazo: {$tarefa->data_vencimento->format('d/m/Y')}");
                    }
                    $this->newLine();
                }
            }

            // Footer com energia atual
            $energia = $profile->energia_atual ?? 'media';
            $energiaEmoji = ['baixa' => '🔴', 'media' => '🟡', 'alta' => '🟢'][$energia] ?? '⚪';
            $this->newLine();
            $this->line("⚡ Energia atual: {$energiaEmoji} ".ucfirst($energia));
            $this->newLine();

            // Mensagem motivacional
            $mensagens = [
                'Você consegue! Um passo de cada vez.',
                'O importante é começar, não ser perfeito.',
                'Cada pequena conquista conta!',
                'Respire. Você está fazendo o melhor que pode.',
            ];
            $this->info('💬 '.$mensagens[array_rand($mensagens)]);
            $this->newLine();

            $this->info('✅ Relatório gerado com sucesso!');
            $this->newLine();
        }

        return 0;
    }
}
