<?php

namespace App\Modules\TreeTask\Support;

use Illuminate\Support\Str;

/**
 * Traduz dados do módulo (status, prioridade, nomes) em classes e cores do visual do TreeTask.
 */
class Aparencia
{
    /** Cores de projeto (texto branco com contraste suficiente). */
    private const CORES_PROJETO = ['#7B4FA8', '#0E7490', '#B4530A', '#BE185D', '#475569', '#15803D', '#1D4ED8', '#9A3412'];

    private const CLASSES_STATUS = [
        'Planejamento' => 'st-pl',
        'A Fazer' => 'st-af',
        'Em Andamento' => 'st-an',
        'Aguardando resposta' => 'st-ag',
        'Concluído' => 'st-co',
    ];

    private const CLASSES_PRIORIDADE = [
        'Urgente' => 'p-urgente',
        'Alta' => 'p-alta',
        'Média' => 'p-media',
        'Baixa' => 'p-baixa',
    ];

    public static function statusClasse(?string $status): string
    {
        return self::CLASSES_STATUS[$status] ?? 'st-af';
    }

    public static function prioridadeClasse(?string $prioridade): string
    {
        return self::CLASSES_PRIORIDADE[$prioridade] ?? 'p-media';
    }

    public static function corProjeto(int $idProjeto): string
    {
        return self::CORES_PROJETO[$idProjeto % count(self::CORES_PROJETO)];
    }

    /** Cor estável de avatar a partir do id do usuário. */
    public static function corAvatar(?int $idUsuario): string
    {
        return self::CORES_PROJETO[($idUsuario ?? 0) % count(self::CORES_PROJETO)];
    }

    public static function iniciais(?string $nome): string
    {
        $partes = preg_split('/\s+/', trim((string) $nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($partes === []) {
            return '?';
        }

        $primeira = Str::upper(Str::substr($partes[0], 0, 1));

        return count($partes) > 1
            ? $primeira.Str::upper(Str::substr(end($partes), 0, 1))
            : $primeira.Str::upper(Str::substr($partes[0], 1, 1));
    }
}
