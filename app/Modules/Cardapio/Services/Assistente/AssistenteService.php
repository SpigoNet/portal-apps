<?php

namespace App\Modules\Cardapio\Services\Assistente;

use App\Models\User;
use App\Modules\Cardapio\Exceptions\EstoqueInsuficienteException;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Orquestra o assistente: IA propõe → validador filtra → usuário confirma → serviços aplicam.
 */
class AssistenteService
{
    public function __construct(
        private readonly InterpretadorComandos $interpretador,
        private readonly ValidadorAcoes $validador,
        private readonly ConsultasAssistente $consultas,
        private readonly ExecutorAcoes $executor,
    ) {}

    /**
     * Interpreta o pedido SEM alterar nada: devolve as ações a confirmar, as respostas a perguntas e o que foi rejeitado.
     *
     * @return array{mensagem: ?string, pendentes: array<int, array<string, mixed>>, respostas: array<int, array<int, string>>, rejeitadas: array<int, string>}
     *
     * @throws IaIndisponivelException
     */
    public function interpretar(Casa $casa, Pessoa $falante, User $usuario, string $texto): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            throw new IaIndisponivelException('Escreva o que você quer fazer.');
        }

        if (mb_strlen($texto) > InterpretadorComandos::MAX_TEXTO) {
            throw new IaIndisponivelException('O pedido é longo demais (máximo '.InterpretadorComandos::MAX_TEXTO.' caracteres). Divida em partes.');
        }

        $proposta = $this->interpretador->interpretar($casa, $falante, $usuario, $texto);
        $validadas = $this->validador->validar($casa, $falante, $proposta['acoes']);

        return [
            'mensagem' => $proposta['mensagem'],
            'pendentes' => $validadas['pendentes'],
            'respostas' => array_map(fn (array $c) => $this->consultas->responder($casa, $c), $validadas['consultas']),
            'rejeitadas' => $validadas['rejeitadas'],
        ];
    }

    /**
     * Aplica as ações confirmadas, uma a uma (cada uma em sua transação): uma falha não desfaz as outras.
     *
     * @param  array<int, array<string, mixed>>  $pendentes  ações validadas guardadas na proposta
     * @param  array<int, string>  $idsConfirmados
     * @return array<int, array{id: string, resumo: string, ok: bool, texto: string, plano_id?: int}>
     */
    public function aplicar(Casa $casa, Pessoa $falante, array $pendentes, array $idsConfirmados): array
    {
        $resultados = [];

        foreach ($pendentes as $acao) {
            if (! in_array($acao['id'], $idsConfirmados, true)) {
                continue;
            }

            try {
                $r = DB::transaction(fn () => $this->executor->aplicar($casa, $falante, $acao));
                $resultados[] = ['id' => $acao['id'], 'resumo' => $acao['resumo'], 'ok' => true] + $r;
            } catch (RuntimeException|InvalidArgumentException|EstoqueInsuficienteException $e) {
                $resultados[] = ['id' => $acao['id'], 'resumo' => $acao['resumo'], 'ok' => false, 'texto' => 'Não aplicado: '.$e->getMessage()];
            } catch (Throwable $e) {
                Log::error('Cardapio: erro ao aplicar ação do assistente', ['acao' => $acao['tipo'], 'erro' => $e->getMessage()]);
                $resultados[] = ['id' => $acao['id'], 'resumo' => $acao['resumo'], 'ok' => false, 'texto' => 'Não aplicado: erro inesperado.'];
            }
        }

        return $resultados;
    }
}
