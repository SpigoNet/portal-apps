<?php

namespace App\Services\AI;

/**
 * Parâmetros de geração de imagem aceitos pelo gateway OpenAI-compatível, compartilhados pelos
 * módulos que guardam padrões por provedor (MundosDeMim, Pidgey).
 */
class ParametrosImagem
{
    /** Opções de lista aceitas por parâmetro (espelham o gateway OpenAI-compatível). */
    public const OPCOES = [
        'quality' => ['low' => 'Baixa (rascunho)', 'medium' => 'Média', 'high' => 'Alta'],
        'background' => ['transparent' => 'Transparente', 'opaque' => 'Opaco'],
        'output_format' => ['png' => 'PNG', 'jpeg' => 'JPEG', 'webp' => 'WebP'],
        'moderation' => ['low' => 'Baixa'],
        'style' => ['vivid' => 'Vívido', 'natural' => 'Natural'],
        'input_fidelity' => ['high' => 'Alta', 'low' => 'Baixa'],
        'reasoning_effort' => ['minimal' => 'Mínimo', 'low' => 'Baixo', 'medium' => 'Médio', 'high' => 'Alto', 'xhigh' => 'Muito alto'],
    ];

    /**
     * Mantém só parâmetros conhecidos e válidos; vazio/inválido é descartado.
     *
     * @param  array<string, mixed>  $entrada
     * @return array<string, string|int>
     */
    public static function sanitizar(array $entrada): array
    {
        $saida = [];

        foreach (self::OPCOES as $chave => $validos) {
            $valor = $entrada[$chave] ?? null;
            $valor = $valor === 'jpg' ? 'jpeg' : $valor;

            if (is_string($valor) && array_key_exists($valor, $validos)) {
                $saida[$chave] = $valor;
            }
        }

        $tamanho = strtolower(trim((string) ($entrada['size'] ?? '')));
        if (preg_match('/^(\d{2,4})x(\d{2,4})$/', $tamanho, $m) && (int) $m[1] >= 16 && (int) $m[1] <= 4096
            && (int) $m[2] >= 16 && (int) $m[2] <= 4096) {
            $saida['size'] = $tamanho;
        }

        $compressao = $entrada['output_compression'] ?? null;
        if ($compressao !== null && $compressao !== '' && is_numeric($compressao)
            && (int) $compressao >= 0 && (int) $compressao <= 100) {
            $saida['output_compression'] = (int) $compressao;
        }

        // transparência exige formato com canal alfa
        if (($saida['background'] ?? null) === 'transparent' && ($saida['output_format'] ?? 'png') === 'jpeg') {
            unset($saida['background']);
        }

        return $saida;
    }
}
