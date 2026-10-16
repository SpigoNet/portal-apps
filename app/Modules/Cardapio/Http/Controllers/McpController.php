<?php

namespace App\Modules\Cardapio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cardapio\Http\Middleware\AutenticarMcp;
use App\Modules\Cardapio\Models\Casa;
use App\Modules\Cardapio\Models\Pessoa;
use App\Modules\Cardapio\Services\Mcp\FerramentasMcp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Servidor MCP (JSON-RPC 2.0 sobre HTTP POST, sem estado) do Cardápio, em /cardapio/mcp.
 */
class McpController extends Controller
{
    private const PROMPT_CONTEXTO = <<<'PROMPT'
Você tem acesso ao MCP "cardapio", o assistente de cozinha da minha casa: estoque com validade, receitas, cardápio semanal e lista de compras. Responda sempre em português (pt-BR).

Como usar:
- Consulte antes de agir. Para "o que tenho?", use consultar_estoque; para "o que vence logo?", estoque_vencendo; para "o que falta?", estoque_baixo e ver_compras; para "o que vamos comer?", ver_cardapio.
- Para ideias de refeição, use sugerir_receitas (ele considera estoque, validade, preferências e restrições de quem está à mesa) em vez de inventar receitas. Só use criar_receita se eu pedir uma receita nova.
- Antes de alterar_receita, chame detalhar_receita para partir dos dados atuais. Se enviar "ingredientes", a lista inteira é substituída, então reenvie todos os que devem ficar.
- Use os nomes exatos que o sistema devolve (alimentos, pessoas, receitas). Se uma ferramenta responder que o nome é ambíguo, pergunte qual eu quis dizer.
- Quantidades sempre com unidade (g, kg, ml, l, un, duzia, pacote, lata, colher_cha, colher_sobremesa, colher_sopa, xicara, copo, pitada, dente, fatia, maco...). Medidas caseiras valem como volume (1 ml ≈ 1 g em ingredientes pesados em gramas). Datas no formato AAAA-MM-DD; se eu disser "amanhã" ou "sexta", converta a partir da data de hoje.

Cuidado com o que altera dados:
- Ferramentas de escrita (adicionar_estoque, baixar_estoque, zerar_estoque, adicionar_restricao, adicionar_preferencia, criar_evento, criar_ausencia, adicionar_compra, gerar_cardapio, criar_receita, alterar_receita) são aplicadas na hora. Se o pedido for vago ou faltar quantidade, pessoa ou data, pergunte em vez de supor.
- Peça confirmação antes de zerar_estoque, de substituir ingredientes de uma receita e de gerar_cardapio numa semana que já tem refeições.
- Restrição do tipo "alergia" é inviolável: nunca sugira nem monte refeição com o alimento. Nada de inventar restrições ou preferências que eu não disse.
- Se uma ferramenta voltar com erro, explique o motivo em uma frase e proponha o próximo passo; não repita a mesma chamada às cegas.

Ao responder: seja direto, liste só o necessário (alimentos que vencem primeiro, o que comprar, a refeição sugerida e por quê) e diga o que foi alterado no sistema.
PROMPT;

    private const VERSOES = ['2025-06-18', '2025-03-26', '2024-11-05'];

    public function __construct(private readonly FerramentasMcp $ferramentas) {}

    public function handle(Request $request): JsonResponse|Response
    {
        $corpo = $request->json()->all();

        if ($corpo === []) {
            return $this->resposta(null, erro: [-32700, 'Parse error']);
        }

        if (array_is_list($corpo)) {
            $respostas = array_values(array_filter(array_map(fn ($m) => is_array($m) ? $this->processar($request, $m) : null, $corpo)));

            return $respostas === [] ? response('', 202) : response()->json($respostas);
        }

        $resposta = $this->processar($request, $corpo);

        return $resposta === null ? response('', 202) : response()->json($resposta);
    }

    /** Página com o endereço do MCP (token na URL) e exemplos de configuração. */
    public function conexao(Request $request): View
    {
        $usuario = $request->user();
        $url = url("/cardapio/mcp/{$usuario->id}/".AutenticarMcp::tokenDe($usuario));

        return view('Cardapio::mcp.conexao', [
            'url' => $url,
            'ferramentas' => $this->ferramentas->definicoes(),
            'prompt' => self::PROMPT_CONTEXTO,
        ]);
    }

    /** O transporte é só POST; não oferecemos stream SSE. */
    public function naoSuportado(): Response
    {
        return response('', 405, ['Allow' => 'POST']);
    }

    /**
     * @param  array<string, mixed>  $msg
     * @return array<string, mixed>|null null para notificações (sem resposta)
     */
    private function processar(Request $request, array $msg): ?array
    {
        $id = $msg['id'] ?? null;
        $metodo = $msg['method'] ?? null;
        $params = is_array($msg['params'] ?? null) ? $msg['params'] : [];

        if (($msg['jsonrpc'] ?? null) !== '2.0' || ! is_string($metodo)) {
            return $this->corpoErro($id, -32600, 'Invalid Request');
        }

        if (! array_key_exists('id', $msg)) {
            return null; // notificação (ex.: notifications/initialized)
        }

        /** @var Casa $casa */
        $casa = $request->attributes->get('casa');
        /** @var Pessoa $pessoa */
        $pessoa = $request->attributes->get('pessoaAtual');

        return match ($metodo) {
            'initialize' => $this->corpoOk($id, [
                'protocolVersion' => in_array($params['protocolVersion'] ?? null, self::VERSOES, true) ? $params['protocolVersion'] : self::VERSOES[0],
                'capabilities' => ['tools' => ['listChanged' => false]],
                'serverInfo' => ['name' => 'Cardapio MCP', 'version' => '1.0.0'],
                'instructions' => 'Assistente de cozinha da casa: estoque com validade, receitas, cardápio semanal e lista de compras. '
                    .'Consulte antes de alterar; quantidades usam unidades como g, kg, ml, l, un, duzia, colher_cha, colher_sopa, xicara.',
            ]),
            'ping' => $this->corpoOk($id, new \stdClass),
            'tools/list' => $this->corpoOk($id, ['tools' => $this->ferramentas->definicoes()]),
            'tools/call' => $this->chamar($id, $casa, $pessoa, $params),
            default => $this->corpoErro($id, -32601, "Method not found: {$metodo}"),
        };
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function chamar(mixed $id, Casa $casa, Pessoa $pessoa, array $params): array
    {
        $nome = $params['name'] ?? null;
        $argumentos = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if (! is_string($nome) || ! $this->ferramentas->existe($nome)) {
            return $this->corpoErro($id, -32602, 'Ferramenta desconhecida.');
        }

        try {
            $resultado = $this->ferramentas->executar($casa, $pessoa, $nome, $argumentos);

            return $this->corpoOk($id, $this->conteudo($resultado, false));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->corpoOk($id, $this->conteudo(['erro' => $e->getMessage()], true));
        } catch (Throwable $e) {
            Log::error('Cardapio MCP: erro na ferramenta', ['ferramenta' => $nome, 'erro' => $e->getMessage()]);

            return $this->corpoOk($id, $this->conteudo(['erro' => 'Erro inesperado ao executar a ferramenta.'], true));
        }
    }

    /**
     * @return array{content: array<int, array{type: string, text: string}>, isError: bool}
     */
    private function conteudo(mixed $dados, bool $erro): array
    {
        return [
            'content' => [['type' => 'text', 'text' => json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR)]],
            'isError' => $erro,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function corpoOk(mixed $id, mixed $resultado): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $resultado];
    }

    /**
     * @return array<string, mixed>
     */
    private function corpoErro(mixed $id, int $codigo, string $mensagem): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $codigo, 'message' => $mensagem]];
    }

    /**
     * @param  array{0: int, 1: string}  $erro
     */
    private function resposta(mixed $id, array $erro): JsonResponse
    {
        return response()->json($this->corpoErro($id, $erro[0], $erro[1]), 400);
    }
}
