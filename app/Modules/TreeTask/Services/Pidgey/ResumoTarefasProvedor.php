<?php

namespace App\Modules\TreeTask\Services\Pidgey;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Pidgey\Contracts\ProvedorConteudo;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Services\VozPersona;
use App\Modules\TreeTask\Models\Tarefa;
use App\Modules\TreeTask\Services\TarefasDoUsuarioService;
use Illuminate\Support\Collection;

/**
 * Tarefas do TreeTask como conteúdo dinâmico do Pidgey. Dois tipos, mesma classe: `resumo_tarefas` (manhã:
 * vencem hoje, atrasadas e prioridades) e `fechamento_tarefas` (fim do dia: o que ficou pendente). A lista é
 * determinística; a persona só escreve a abertura. Sem nada a dizer, não envia.
 */
class ResumoTarefasProvedor implements ProvedorConteudo
{
    public const TIPO_MANHA = 'resumo_tarefas';

    public const TIPO_FECHAMENTO = 'fechamento_tarefas';

    private const EMOJI = ['Urgente' => '🔴', 'Alta' => '🟠', 'Média' => '🟡', 'Baixa' => '🔵'];

    public function __construct(
        private readonly string $tipo,
        private readonly TarefasDoUsuarioService $tarefas,
        private readonly VozPersona $voz,
    ) {}

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function mensagem(Agendamento $agendamento, Persona $persona): ?string
    {
        $userId = (int) $agendamento->user_id;
        $fechamento = $this->tipo === self::TIPO_FECHAMENTO;

        $hoje = $this->tarefas->hoje($userId);
        $atrasadas = $this->tarefas->atrasadas($userId);
        $prioridades = $fechamento
            ? new Collection
            : $this->tarefas->topPrioridade($userId)->reject(fn (Tarefa $t) => $hoje->contains('id_tarefa', $t->id_tarefa) || $atrasadas->contains('id_tarefa', $t->id_tarefa));

        if ($hoje->isEmpty() && $atrasadas->isEmpty() && $prioridades->isEmpty()) {
            return null;
        }

        // [rótulo em HTML, nome em texto puro, tarefas]
        $grupos = [
            ['⚠ <b>Atrasadas</b>', 'Atrasadas', $atrasadas],
            [$fechamento ? '📌 <b>Vencem hoje e seguem abertas</b>' : '🎯 <b>Vencem hoje</b>', 'Vencem hoje', $hoje],
            ['⭐ <b>Prioridades</b>', 'Outras prioridades', $prioridades],
        ];

        $abertura = $this->voz->abrir($persona, $this->fatos($grupos), $fechamento ? $this->instrucaoFechamento() : $this->instrucaoManha())
            ?? e($fechamento ? 'Fim do expediente: veja o que ficou pendente.' : 'Bom dia! Estas são as tarefas de hoje.');

        return $abertura."\n\n".$this->bloco($grupos);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: Collection<int, Tarefa>}>  $grupos
     */
    private function bloco(array $grupos): string
    {
        $blocos = [];

        foreach ($grupos as [$rotulo, , $tarefas]) {
            if ($tarefas->isEmpty()) {
                continue;
            }

            $blocos[] = $rotulo."\n".$tarefas->map(fn (Tarefa $t): string => $this->linha($t))->implode("\n");
        }

        return implode("\n\n", $blocos);
    }

    private function linha(Tarefa $t): string
    {
        $projeto = $t->fase?->projeto?->nome;
        $prazo = $t->data_vencimento?->format('d/m');

        return (self::EMOJI[$t->prioridade] ?? '⚪').' '.e($t->titulo)
            .($projeto ? ' · '.e($projeto) : '')
            .($prazo ? " ({$prazo})" : '');
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: Collection<int, Tarefa>}>  $grupos
     */
    private function fatos(array $grupos): string
    {
        $linhas = [];

        foreach ($grupos as [, $nome, $tarefas]) {
            $linhas[] = $tarefas->isEmpty()
                ? "{$nome}: nenhuma."
                : "{$nome}: ".$tarefas->map(fn (Tarefa $t): string => trim("{$t->titulo} (prioridade ".($t->prioridade ?: 'não definida').')'))->implode('; ').'.';
        }

        return implode("\n", $linhas);
    }

    private function instrucaoManha(): string
    {
        return 'A lista completa das tarefas será ACRESCENTADA AUTOMATICAMENTE logo abaixo do seu texto: NÃO a repita. Comece o expediente com foco: cobre as atrasadas, destaque a mais urgente de hoje (citando o título exatamente como está nos fatos) e, se não há atrasos, seja encorajador.';
    }

    private function instrucaoFechamento(): string
    {
        return 'A lista do que ficou pendente será ACRESCENTADA AUTOMATICAMENTE logo abaixo do seu texto: NÃO a repita. É o fim do expediente: cobre o que ficou para trás sem enrolação, sugira por onde recomeçar amanhã e lembre de descansar.';
    }
}
