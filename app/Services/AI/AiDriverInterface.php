<?php

namespace App\Services\AI;

interface AiDriverInterface
{
    /**
     * Gera texto/JSON a partir de um array de mensagens
     *
     * @param  array  $messages  Ex: [['role' => 'user', 'content' => '...']]
     * @param  array  $options  Ex: ['jsonMode' => true]
     */
    public function generateText(array $messages, array $options = []): ?string;

    /**
     * Gera imagem a partir de um prompt
     *
     * @param  array  $options  Ex: ['size' => '1024x1024', 'n' => 1]
     */
    public function generateImage(string $prompt, array $options = []): ?string;
}
