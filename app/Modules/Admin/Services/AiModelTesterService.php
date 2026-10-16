<?php

namespace App\Modules\Admin\Services;

use App\Models\AiModelDefault;
use Illuminate\Log\Events\MessageLogged;

class AiModelTesterService
{
    public function __construct(private readonly AiProviderService $providers) {}

    /**
     * Executa um teste real contra o modelo padrão informado.
     *
     * @return array{status: string, mensagem: string, detalhe: ?string, duracao_ms: int}
     */
    public function testar(AiModelDefault $padrao): array
    {
        $inicio = microtime(true);
        $erros = [];

        app('events')->listen(MessageLogged::class, function (MessageLogged $evento) use (&$erros): void {
            if (in_array($evento->level, ['error', 'critical', 'warning'], true)) {
                $erros[] = $evento->message;
            }
        });

        try {
            $resultado = $this->executar($padrao);
        } catch (\Throwable $e) {
            $resultado = ['status' => 'erro', 'mensagem' => $e->getMessage(), 'detalhe' => null];
        }

        if ($resultado['status'] === 'erro' && $resultado['detalhe'] === null && $erros !== []) {
            $resultado['detalhe'] = mb_substr(implode("\n", array_slice($erros, -3)), 0, 600);
        }

        if ($resultado['status'] === 'erro' && $padrao->modelo?->provedor) {
            $resultado['detalhe'] = trim(($resultado['detalhe'] ?? '')."\n".sprintf(
                '[driver: %s · url: %s · modelo: %s]',
                $this->providers->getDriverForProvider($padrao->modelo),
                $padrao->modelo->provedor->base_url ?: '(padrão do driver)',
                $padrao->modelo->model
            ));
        }

        $resultado['duracao_ms'] = (int) round((microtime(true) - $inicio) * 1000);

        return $resultado;
    }

    /**
     * @return array{status: string, mensagem: string, detalhe: ?string}
     */
    private function executar(AiModelDefault $padrao): array
    {
        $modelo = $padrao->modelo;

        if (! $modelo || ! $modelo->provedor) {
            return ['status' => 'erro', 'mensagem' => 'Modelo ou provedor padrão não encontrado.', 'detalhe' => null];
        }

        if (! $modelo->is_active || ! $modelo->provedor->is_active) {
            return ['status' => 'erro', 'mensagem' => 'Modelo ou provedor está inativo.', 'detalhe' => null];
        }

        $driver = $this->providers->createTextDriver($modelo);

        if ($padrao->input_type === 'text' && $padrao->output_type === 'text') {
            $resposta = $driver->generateText([
                ['role' => 'user', 'content' => 'Responda apenas com a palavra: OK'],
            ]);

            return $this->avaliar(is_string($resposta) && trim($resposta) !== '', 'Texto recebido: '.mb_substr(trim((string) $resposta), 0, 80));
        }

        if ($padrao->input_type === 'text' && $padrao->output_type === 'image') {
            $resposta = $driver->generateImage('Um círculo vermelho simples sobre fundo branco');

            return $this->avaliar(! empty($resposta), 'Imagem gerada.');
        }

        return [
            'status' => 'ignorado',
            'mensagem' => "Teste automático indisponível para {$padrao->input_type} → {$padrao->output_type}.",
            'detalhe' => null,
        ];
    }

    /**
     * @return array{status: string, mensagem: string, detalhe: ?string}
     */
    private function avaliar(bool $ok, string $mensagemOk): array
    {
        return $ok
            ? ['status' => 'ok', 'mensagem' => $mensagemOk, 'detalhe' => null]
            : ['status' => 'erro', 'mensagem' => 'O provedor não retornou resposta.', 'detalhe' => null];
    }
}
