<?php

use App\Models\User;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\ConteudoDinamico;
use App\Modules\Pidgey\Models\TelegramVinculo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dia a dia no Pidgey: cria a persona Zoro (tarefas do trabalho), os conteúdos dinâmicos novos e os
 * agendamentos padrão (resumo às 08:30 e fechamento às 18:00, em dias úteis). O Luffy passa a usar o contexto
 * do dia no lugar do texto fixo. O token do bot do Zoro (@BotFather) é preenchido depois, na persona.
 */
return new class extends Migration
{
    private const CONTEUDOS = [
        'relatorio_financeiro' => 'Relatório Financeiro (Nami)',
        'contas_a_pagar' => 'Contas a pagar (Nami)',
        'resumo_tarefas' => 'Tarefas do dia (Zoro)',
        'fechamento_tarefas' => 'Fechamento das tarefas (Zoro)',
        'contexto_dia' => 'Motivação com contexto do dia (Luffy)',
    ];

    public function up(): void
    {
        foreach (['alfred_personas', 'pidgey_agendamentos', 'pidgey_conteudos_dinamicos'] as $tabela) {
            if (! Schema::hasTable($tabela)) {
                return;
            }
        }

        $this->criarPersonaZoro();

        $conteudos = [];
        foreach (self::CONTEUDOS as $tipo => $nome) {
            $conteudos[$tipo] = ConteudoDinamico::firstOrCreate(
                ['tipo' => $tipo],
                ['nome' => $nome, 'conteudo' => null, 'ativo' => true, 'sistema' => true],
            );
        }

        foreach ($this->usuarios() as $userId) {
            $this->agendar($userId, 'zoro', 'Tarefas do dia', '08:30', $conteudos['resumo_tarefas']);
            $this->agendar($userId, 'zoro', 'Fechamento das tarefas', '18:00', $conteudos['fechamento_tarefas']);
            $this->trocarMotivacaoDoLuffy($userId, $conteudos['contexto_dia']);
        }
    }

    public function down(): void {}

    /**
     * Dono do portal (como no seed do Luffy) e quem já vinculou o Telegram.
     *
     * @return array<int, int>
     */
    private function usuarios(): array
    {
        $ids = Schema::hasTable('pidgey_telegram_vinculos') ? TelegramVinculo::query()->pluck('user_id')->all() : [];

        if (User::where('id', 1)->exists()) {
            $ids[] = 1;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function criarPersonaZoro(): void
    {
        if (DB::table('alfred_personas')->where('slug', 'zoro')->exists()) {
            return;
        }

        $dados = [
            'name' => 'Zoro',
            'slug' => 'zoro',
            'canal' => 'telegram',
            'personality' => json_encode([
                'description' => 'Roronoa Zoro, o espadachim do bando (One Piece): disciplinado, direto, de poucas palavras e muita cobrança. Leva o treino e a responsabilidade a sério, não aceita desculpa nem enrolação, mas respeita quem se esforça.',
                'tone' => 'seco, firme, direto',
                'greetings' => ['Foco.'],
            ], JSON_UNESCAPED_UNICODE),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Colunas que podem não existir em todos os ambientes.
        DB::table('alfred_personas')->insert(array_intersect_key($dados, array_flip(Schema::getColumnListing('alfred_personas'))));
    }

    private function agendar(int $userId, string $persona, string $mensagem, string $hora, ConteudoDinamico $conteudo): void
    {
        $existe = Agendamento::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('persona_slug', $persona)
            ->whereHas('conteudosDinamicos', fn ($q) => $q->where('pidgey_conteudos_dinamicos.id', $conteudo->id))
            ->exists();

        if ($existe) {
            return;
        }

        $agendamento = new Agendamento([
            'user_id' => $userId,
            'persona_slug' => $persona,
            'canal' => 'telegram',
            'mensagem' => $mensagem,
            'interpretar' => false,
            'frequencia' => 'diario',
            'hora' => $hora,
            'dias_semana' => [1, 2, 3, 4, 5],
            'ativo' => true,
        ]);
        $agendamento->proxima_execucao = $agendamento->calcularProximaExecucao(now());
        $agendamento->save();
        $agendamento->conteudosDinamicos()->attach($conteudo->id);
    }

    /**
     * O agendamento diário do Luffy (texto fixo) passa a usar o contexto do dia, mantendo horário e canal.
     */
    private function trocarMotivacaoDoLuffy(int $userId, ConteudoDinamico $contexto): void
    {
        $agendamentos = Agendamento::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('persona_slug', 'luffy')
            ->whereHas('conteudosDinamicos', fn ($q) => $q->where('tipo', 'texto')->where('nome', 'Mensagem Motivacional (Luffy)'))
            ->get();

        foreach ($agendamentos as $agendamento) {
            $agendamento->conteudosDinamicos()->detach(
                $agendamento->conteudosDinamicos->where('nome', 'Mensagem Motivacional (Luffy)')->pluck('id')->all()
            );
            $agendamento->conteudosDinamicos()->syncWithoutDetaching([$contexto->id]);
        }
    }
};
