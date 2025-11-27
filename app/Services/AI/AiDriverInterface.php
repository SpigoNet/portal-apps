<?php

namespace App\Services\AI;

interface AiDriverInterface
{
    /**
     * Gera texto/JSON a partir de um array de mensagens
     *
     * @param array $messages Ex: [['role' => 'user', 'content' => '...']]
     * @param array $options Ex: ['jsonMode' => true]
     * @return string|null
     */
    public function generateText(array $messages, array $options = []): ?string;
}
