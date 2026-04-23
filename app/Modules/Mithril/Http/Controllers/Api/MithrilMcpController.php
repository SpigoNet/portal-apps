<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Mithril\Services\MithrilMcpServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MithrilMcpController extends Controller
{
    private ?User $user = null;

    private MithrilMcpServer $mcpServer;

    public function __construct()
    {
        $this->mcpServer = new MithrilMcpServer;
    }

    public function handle(Request $request): JsonResponse
    {
        $authResult = $this->authenticate($request);
        if ($authResult instanceof JsonResponse) {
            return $authResult;
        }

        $this->user = $authResult;
        $this->mcpServer->setUser($this->user);

        $body = $request->all();

        if (isset($body['method'])) {
            return $this->handleJsonRpc($body);
        }

        if (isset($body[0])) {
            $responses = [];
            foreach ($body as $item) {
                $responses[] = $this->handleJsonRpc($item);
            }

            return response()->json(array_map(fn ($r) => $r->getData(true), $responses));
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32600,
                'message' => 'Invalid Request',
            ],
        ]);
    }

    private function handleJsonRpc(array $request): JsonResponse
    {
        $jsonrpc = $request['jsonrpc'] ?? null;
        $method = $request['method'] ?? null;
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;

        if ($jsonrpc !== '2.0' || ! $method) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32600,
                    'message' => 'Invalid Request',
                ],
            ]);
        }

        if ($method === 'initialize') {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => (object) [],
                    ],
                    'serverInfo' => [
                        'name' => 'Mithril MCP Server',
                        'version' => '1.0.0',
                    ],
                ],
            ]);
        }

        if ($method === 'tools/list') {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'tools' => [
                        [
                            'name' => 'list_contas',
                            'description' => 'Lista todas as contas do usuário',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => new \stdClass,
                            ],
                        ],
                        [
                            'name' => 'create_conta',
                            'description' => 'Cria uma nova conta',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'nome' => ['type' => 'string', 'description' => 'Nome da conta'],
                                    'tipo' => ['type' => 'string', 'enum' => ['normal', 'credito'], 'description' => 'Tipo'],
                                    'saldo_inicial' => ['type' => 'number', 'description' => 'Saldo inicial'],
                                    'dia_fechamento' => ['type' => 'integer', 'description' => 'Dia de fechamento'],
                                    'dia_vencimento' => ['type' => 'integer', 'description' => 'Dia de vencimento'],
                                ],
                                'required' => ['nome', 'tipo'],
                            ],
                        ],
                        [
                            'name' => 'get_conta',
                            'description' => 'Busca uma conta pelo ID',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'description' => 'ID da conta'],
                                ],
                                'required' => ['id'],
                            ],
                        ],
                        [
                            'name' => 'update_conta',
                            'description' => 'Atualiza uma conta',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'description' => 'ID da conta'],
                                    'nome' => ['type' => 'string', 'description' => 'Nome da conta'],
                                    'tipo' => ['type' => 'string', 'enum' => ['normal', 'credito'], 'description' => 'Tipo'],
                                    'saldo_inicial' => ['type' => 'number', 'description' => 'Saldo inicial'],
                                    'dia_fechamento' => ['type' => 'integer', 'description' => 'Dia de fechamento'],
                                    'dia_vencimento' => ['type' => 'integer', 'description' => 'Dia de vencimento'],
                                ],
                                'required' => ['id'],
                            ],
                        ],
                        [
                            'name' => 'delete_conta',
                            'description' => 'Remove uma conta',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'description' => 'ID da conta'],
                                ],
                                'required' => ['id'],
                            ],
                        ],
                        [
                            'name' => 'list_transacoes',
                            'description' => 'Lista transações de um mês específico',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'mes' => ['type' => 'integer', 'description' => 'Mês (1-12)'],
                                    'ano' => ['type' => 'integer', 'description' => 'Ano'],
                                    'conta_id' => ['type' => 'integer', 'description' => 'ID da conta'],
                                ],
                            ],
                        ],
                        [
                            'name' => 'create_transacao',
                            'description' => 'Cria uma nova transação',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'descricao' => ['type' => 'string', 'description' => 'Descrição'],
                                    'valor' => ['type' => 'number', 'description' => 'Valor'],
                                    'data_efetiva' => ['type' => 'string', 'description' => 'Data (Y-m-d)'],
                                    'conta_id' => ['type' => 'integer', 'description' => 'ID da conta'],
                                    'operacao' => ['type' => 'string', 'enum' => ['debito', 'credito'], 'description' => 'Operação'],
                                ],
                                'required' => ['descricao', 'valor', 'data_efetiva', 'conta_id', 'operacao'],
                            ],
                        ],
                        [
                            'name' => 'delete_transacao',
                            'description' => 'Remove uma transação',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'description' => 'ID da transação'],
                                ],
                                'required' => ['id'],
                            ],
                        ],
                        [
                            'name' => 'list_pre_transacoes',
                            'description' => 'Lista todas as pré-transações',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => new \stdClass,
                            ],
                        ],
                        [
                            'name' => 'create_pre_transacao',
                            'description' => 'Cria uma nova pré-transação',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'descricao' => ['type' => 'string', 'description' => 'Descrição'],
                                    'valor_parcela' => ['type' => 'number', 'description' => 'Valor da parcela'],
                                    'conta_id' => ['type' => 'integer', 'description' => 'ID da conta'],
                                    'dia_vencimento' => ['type' => 'integer', 'description' => 'Dia de vencimento (1-31)'],
                                    'tipo' => ['type' => 'string', 'enum' => ['recorrente', 'parcelada'], 'description' => 'Tipo'],
                                    'operacao' => ['type' => 'string', 'enum' => ['debito', 'credito'], 'description' => 'Operação'],
                                    'total_parcelas' => ['type' => 'integer', 'description' => 'Total de parcelas'],
                                    'data_inicio' => ['type' => 'string', 'description' => 'Data de início (Y-m-d)'],
                                ],
                                'required' => ['descricao', 'valor_parcela', 'conta_id', 'dia_vencimento', 'tipo', 'operacao'],
                            ],
                        ],
                        [
                            'name' => 'toggle_pre_transacao',
                            'description' => 'Ativa ou desativa uma pré-transação',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'description' => 'ID da pré-transação'],
                                ],
                                'required' => ['id'],
                            ],
                        ],
                        [
                            'name' => 'efetivar_pre_transacao',
                            'description' => 'Efetiva uma parcela de pré-transação',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'description' => 'ID da pré-transação'],
                                    'mes' => ['type' => 'integer', 'description' => 'Mês (1-12)'],
                                    'ano' => ['type' => 'integer', 'description' => 'Ano'],
                                ],
                                'required' => ['id'],
                            ],
                        ],
                        [
                            'name' => 'delete_pre_transacao',
                            'description' => 'Remove uma pré-transação',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'description' => 'ID da pré-transação'],
                                ],
                                'required' => ['id'],
                            ],
                        ],
                    ],
                ],
            ]);
        }

        if ($method === 'tools/call') {
            $toolName = $params['name'] ?? null;
            $arguments = $params['arguments'] ?? [];

            if (! $toolName) {
                return response()->json([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32602,
                        'message' => 'Invalid params',
                    ],
                ]);
            }

            try {
                $result = $this->callTool($toolName, $arguments);
            } catch (\Throwable $e) {
                return response()->json([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32603,
                        'message' => 'Internal Error: '.$e->getMessage(),
                    ],
                ]);
            }

            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ]);
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32601,
                'message' => 'Method not found',
            ],
        ]);
    }

    private function callTool(string $name, array $arguments): array
    {
        return match ($name) {
            'list_contas' => $this->mcpServer->listContas(),
            'create_conta' => $this->mcpServer->createConta(
                $arguments['nome'],
                $arguments['tipo'],
                $arguments['saldo_inicial'] ?? null,
                $arguments['dia_fechamento'] ?? null,
                $arguments['dia_vencimento'] ?? null,
            ),
            'get_conta' => $this->mcpServer->getConta($arguments['id']),
            'update_conta' => $this->mcpServer->updateConta(
                $arguments['id'],
                $arguments['nome'] ?? null,
                $arguments['tipo'] ?? null,
                $arguments['saldo_inicial'] ?? null,
                $arguments['dia_fechamento'] ?? null,
                $arguments['dia_vencimento'] ?? null,
            ),
            'delete_conta' => $this->mcpServer->deleteConta($arguments['id']),
            'list_transacoes' => $this->mcpServer->listTransacoes(
                $arguments['mes'] ?? null,
                $arguments['ano'] ?? null,
                $arguments['conta_id'] ?? null,
            ),
            'create_transacao' => $this->mcpServer->createTransacao(
                $arguments['descricao'],
                $arguments['valor'],
                $arguments['data_efetiva'],
                $arguments['conta_id'],
                $arguments['operacao'],
            ),
            'delete_transacao' => $this->mcpServer->deleteTransacao($arguments['id']),
            'list_pre_transacoes' => $this->mcpServer->listPreTransacoes(),
            'create_pre_transacao' => $this->mcpServer->createPreTransacao(
                $arguments['descricao'],
                $arguments['valor_parcela'],
                $arguments['conta_id'],
                $arguments['dia_vencimento'],
                $arguments['tipo'],
                $arguments['operacao'],
                $arguments['total_parcelas'] ?? null,
                $arguments['data_inicio'] ?? null,
            ),
            'toggle_pre_transacao' => $this->mcpServer->togglePreTransacao($arguments['id']),
            'efetivar_pre_transacao' => $this->mcpServer->efetivarPreTransacao(
                $arguments['id'],
                $arguments['mes'] ?? null,
                $arguments['ano'] ?? null,
            ),
            'delete_pre_transacao' => $this->mcpServer->deletePreTransacao($arguments['id']),
            default => throw new \RuntimeException("Tool {$name} not found"),
        };
    }

    private function authenticate(Request $request): User|JsonResponse
    {
        $userId = $request->header('X-User-ID');
        $token = $request->header('X-Token');

        if (! $userId || ! $token) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'Credenciais não fornecidas. Envie X-User-ID e X-Token no header.',
                ],
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'Usuário não encontrado.',
                ],
            ], 401);
        }

        $expectedToken = md5($user->email.$user->password);

        if ($token !== $expectedToken) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'Token inválido.',
                ],
            ], 401);
        }

        return $user;
    }
}
