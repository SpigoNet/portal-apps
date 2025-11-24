<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollinationService
{
    /**
     * Endpoint base da API.
     */
    protected string $endpoint = 'https://text.pollinations.ai/openai';

    /**
     * Envia uma requisição para gerar texto (Chat Completion).
     *
     * @param array $messages Lista de mensagens (ex: [['role' => 'system', 'content' => '...']])
     * @param array $options Opções adicionais (temperature, max_tokens, model, seed, jsonMode)
     * @return string|null Retorna o conteúdo da resposta ou null em caso de erro.
     */
    public function generateText(array $messages, array $options = []): ?string
    {
        // Mescla as opções padrão com as passadas pelo controller
        $payload = array_merge([
            'model' => 'openai', // Modelo padrão
            'temperature' => 1,
            'max_tokens' => 500,
            'stream' => false,
            'messages' => $messages,
        ], $options);
        Log::error('Tentando Pollination API');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // A estrutura de retorno segue o padrão OpenAI: choices[0].message.content
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('Pollination API Error: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Pollination Connection Error: ' . $e->getMessage());
            return null;
        }
    }
}
