<?php

namespace App\Modules\Mithril\Services;

use App\Models\User;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MithrilMcpServer
{
    private ?User $user = null;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function listContas(): array
    {
        $contas = Conta::all();

        return [
            'contas' => $contas->map(fn ($c) => [
                'id' => $c->id,
                'nome' => $c->nome,
                'tipo' => $c->tipo,
                'saldo_inicial' => (float) $c->saldo_inicial,
                'dia_fechamento' => $c->dia_fechamento,
                'dia_vencimento' => $c->dia_vencimento,
            ])->toArray(),
        ];
    }

    public function createConta(string $nome, string $tipo, ?float $saldo_inicial = 0, ?int $dia_fechamento = null, ?int $dia_vencimento = null): array
    {
        $data = [
            'nome' => $nome,
            'tipo' => $tipo,
            'saldo_inicial' => $saldo_inicial,
            'dia_fechamento' => $dia_fechamento,
            'dia_vencimento' => $dia_vencimento,
        ];

        $validated = Validator::make($data, [
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:normal,credito',
            'saldo_inicial' => 'nullable|numeric',
            'dia_fechamento' => 'nullable|integer|min:1|max:31',
            'dia_vencimento' => 'nullable|integer|min:1|max:31',
        ])->validate();

        $validated['user_id'] = $this->user->id;
        $validated['saldo_inicial'] = $validated['saldo_inicial'] ?? 0;

        $conta = Conta::create($validated);

        return [
            'id' => $conta->id,
            'nome' => $conta->nome,
            'tipo' => $conta->tipo,
            'saldo_inicial' => (float) $conta->saldo_inicial,
            'message' => 'Conta criada com sucesso.',
        ];
    }

    public function getConta(int $id): array
    {
        $conta = Conta::findOrFail($id);

        return [
            'id' => $conta->id,
            'nome' => $conta->nome,
            'tipo' => $conta->tipo,
            'saldo_inicial' => (float) $conta->saldo_inicial,
            'dia_fechamento' => $conta->dia_fechamento,
            'dia_vencimento' => $conta->dia_vencimento,
            'conta_debito_id' => $conta->conta_debito_id,
        ];
    }

    public function updateConta(int $id, ?string $nome = null, ?string $tipo = null, ?float $saldo_inicial = null, ?int $dia_fechamento = null, ?int $dia_vencimento = null): array
    {
        $conta = Conta::findOrFail($id);

        $data = array_filter(compact('nome', 'tipo', 'saldo_inicial', 'dia_fechamento', 'dia_vencimento'), fn ($v) => $v !== null && $v !== '');

        if (empty($data)) {
            return [
                'id' => $conta->id,
                'nome' => $conta->nome,
                'message' => 'Nenhuma alteração fornecida.',
            ];
        }

        $validated = Validator::make($data, [
            'nome' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|in:normal,credito',
            'saldo_inicial' => 'nullable|numeric',
            'dia_fechamento' => 'nullable|integer|min:1|max:31',
            'dia_vencimento' => 'nullable|integer|min:1|max:31',
        ])->validate();

        $conta->update($validated);

        return [
            'id' => $conta->id,
            'nome' => $conta->nome,
            'tipo' => $conta->tipo,
            'message' => 'Conta atualizada com sucesso.',
        ];
    }

    public function deleteConta(int $id): array
    {
        $conta = Conta::findOrFail($id);
        $conta->delete();

        return ['message' => 'Conta removida com sucesso.'];
    }

    public function listTransacoes(?int $mes = null, ?int $ano = null, ?int $conta_id = null): array
    {
        $mes = $mes ?? Carbon::now()->month;
        $ano = $ano ?? Carbon::now()->year;

        $query = Transacao::with('conta')
            ->whereMonth('data_efetiva', $mes)
            ->whereYear('data_efetiva', $ano)
            ->orderBy('data_efetiva', 'asc');

        if ($conta_id) {
            $query->where('conta_id', $conta_id);
        }

        $transacoes = $query->get();

        $totalEntradas = $transacoes->where('valor', '>=', 0)->sum('valor');
        $totalSaidas = $transacoes->where('valor', '<', 0)->sum('valor');

        return [
            'transacoes' => $transacoes->map(fn ($t) => [
                'id' => $t->id,
                'descricao' => $t->descricao,
                'valor' => (float) $t->valor,
                'data_efetiva' => $t->data_efetiva?->format('Y-m-d'),
                'conta' => $t->conta ? ['id' => $t->conta->id, 'nome' => $t->conta->nome] : null,
            ])->toArray(),
            'meta' => [
                'mes' => (int) $mes,
                'ano' => (int) $ano,
                'total_entradas' => (float) $totalEntradas,
                'total_saidas' => (float) $totalSaidas,
                'saldo_mes' => (float) ($totalEntradas + $totalSaidas),
                'total_registros' => $transacoes->count(),
            ],
        ];
    }

    public function createTransacao(string $descricao, float $valor, string $data_efetiva, int $conta_id, string $operacao): array
    {
        $data = [
            'descricao' => $descricao,
            'valor' => $valor,
            'data_efetiva' => $data_efetiva,
            'conta_id' => $conta_id,
            'operacao' => $operacao,
        ];

        $validated = Validator::make($data, [
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric',
            'data_efetiva' => 'required|date',
            'conta_id' => 'required|exists:mithril_contas,id',
            'operacao' => 'required|in:debito,credito',
        ])->validate();

        $valorFinal = $operacao === 'debito' ? -abs($valor) : abs($valor);

        $transacao = Transacao::create([
            'descricao' => $validated['descricao'],
            'valor' => $valorFinal,
            'data_efetiva' => $validated['data_efetiva'],
            'conta_id' => $validated['conta_id'],
        ]);

        return [
            'id' => $transacao->id,
            'descricao' => $transacao->descricao,
            'valor' => (float) $transacao->valor,
            'data_efetiva' => $transacao->data_efetiva?->format('Y-m-d'),
            'message' => 'Transação criada com sucesso.',
        ];
    }

    public function deleteTransacao(int $id): array
    {
        $transacao = Transacao::findOrFail($id);
        $transacao->delete();

        return ['message' => 'Transação removida com sucesso.'];
    }

    public function listPreTransacoes(): array
    {
        $preTransacoes = PreTransacao::with('conta')
            ->orderBy('ativa', 'desc')
            ->orderBy('dia_vencimento')
            ->get();

        return [
            'pre_transacoes' => $preTransacoes->map(fn ($pt) => [
                'id' => $pt->id,
                'descricao' => $pt->descricao,
                'valor_parcela' => (float) $pt->valor_parcela,
                'conta' => $pt->conta ? ['id' => $pt->conta->id, 'nome' => $pt->conta->nome] : null,
                'dia_vencimento' => $pt->dia_vencimento,
                'tipo' => $pt->tipo,
                'total_parcelas' => $pt->total_parcelas,
                'parcela_atual' => $pt->parcela_atual,
                'ativa' => $pt->ativa,
            ])->toArray(),
        ];
    }

    public function createPreTransacao(
        string $descricao,
        float $valor_parcela,
        int $conta_id,
        int $dia_vencimento,
        string $tipo,
        string $operacao,
        ?int $total_parcelas = null,
        ?string $data_inicio = null
    ): array {
        $data = [
            'descricao' => $descricao,
            'valor_parcela' => $valor_parcela,
            'conta_id' => $conta_id,
            'dia_vencimento' => $dia_vencimento,
            'tipo' => $tipo,
            'operacao' => $operacao,
            'total_parcelas' => $total_parcelas,
            'data_inicio' => $data_inicio,
        ];

        $rules = [
            'descricao' => 'required|string|max:255',
            'valor_parcela' => 'required|numeric',
            'conta_id' => 'required|exists:mithril_contas,id',
            'dia_vencimento' => 'required|integer|min:1|max:31',
            'tipo' => 'required|in:recorrente,parcelada',
            'operacao' => 'required|in:debito,credito',
            'total_parcelas' => 'required_if:tipo,parcelada|nullable|integer|min:1',
            'data_inicio' => 'required_if:tipo,parcelada|nullable|date',
        ];

        $validated = Validator::make($data, $rules)->validate();

        $valor = $operacao === 'debito' ? -abs($valor_parcela) : abs($valor_parcela);

        $dados = [
            'user_id' => $this->user->id,
            'descricao' => $validated['descricao'],
            'valor_parcela' => $valor,
            'conta_id' => $validated['conta_id'],
            'dia_vencimento' => $validated['dia_vencimento'],
            'tipo' => $validated['tipo'],
            'ativa' => true,
        ];

        if ($validated['tipo'] === 'recorrente') {
            $dados['total_parcelas'] = null;
            $dados['parcela_atual'] = 0;
        } else {
            $dados['total_parcelas'] = $validated['total_parcelas'];
            $dados['parcela_atual'] = 0;
            $dados['data_inicio'] = $validated['data_inicio'];
        }

        $preTransacao = PreTransacao::create($dados);

        return [
            'id' => $preTransacao->id,
            'descricao' => $preTransacao->descricao,
            'valor_parcela' => (float) $preTransacao->valor_parcela,
            'tipo' => $preTransacao->tipo,
            'message' => 'Pré-transação criada com sucesso.',
        ];
    }

    public function togglePreTransacao(int $id): array
    {
        $pt = PreTransacao::findOrFail($id);
        $pt->ativa = ! $pt->ativa;
        $pt->save();

        return [
            'ativa' => $pt->ativa,
            'message' => $pt->ativa ? 'Pré-transação ativada.' : 'Pré-transação desativada.',
        ];
    }

    public function efetivarPreTransacao(int $id, ?int $mes = null, ?int $ano = null): array
    {
        $pt = PreTransacao::findOrFail($id);
        $mes = $mes ?? Carbon::now()->month;
        $ano = $ano ?? Carbon::now()->year;

        $transacao = Transacao::create([
            'user_id' => $this->user->id,
            'conta_id' => $pt->conta_id,
            'pre_transacao_id' => $pt->id,
            'descricao' => $pt->descricao,
            'valor' => $pt->valor_parcela,
            'data_efetiva' => Carbon::create($ano, $mes, $pt->dia_vencimento)->format('Y-m-d'),
        ]);

        if ($pt->tipo === 'parcelada') {
            $pt->increment('parcela_atual');
        }

        return [
            'id' => $transacao->id,
            'descricao' => $transacao->descricao,
            'valor' => (float) $transacao->valor,
            'data_efetiva' => $transacao->data_efetiva?->format('Y-m-d'),
            'message' => 'Parcela efetivada com sucesso.',
        ];
    }

    public function deletePreTransacao(int $id): array
    {
        $preTransacao = PreTransacao::findOrFail($id);
        $preTransacao->delete();

        return ['message' => 'Pré-transação removida com sucesso.'];
    }
}
