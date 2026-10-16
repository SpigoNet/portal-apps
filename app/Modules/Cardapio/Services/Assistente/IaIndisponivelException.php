<?php

namespace App\Modules\Cardapio\Services\Assistente;

use RuntimeException;

/**
 * A IA não pôde interpretar o pedido (sem provedor, erro do provedor ou resposta fora do formato).
 * A mensagem é segura para mostrar ao usuário.
 */
class IaIndisponivelException extends RuntimeException {}
