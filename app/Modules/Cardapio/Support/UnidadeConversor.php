<?php

namespace App\Modules\Cardapio\Support;

use InvalidArgumentException;

/**
 * Converte quantidades para a unidade base do ingrediente (g, ml ou un).
 */
class UnidadeConversor
{
    /** @var array<string, array{0: string, 1: float}> unidade => [base, fator] */
    private const UNIDADES = [
        'g' => ['g', 1],
        'kg' => ['g', 1000],
        'ml' => ['ml', 1],
        'l' => ['ml', 1000],
        'un' => ['un', 1],
        'duzia' => ['un', 12],
        'pacote' => ['un', 1],
        'lata' => ['un', 1],
        'caixa' => ['un', 1],
        'pote' => ['un', 1],
        'garrafa' => ['un', 1],
        // Medidas caseiras (volume; em ingredientes controlados em g valem como 1 ml ≈ 1 g).
        'colher_cha' => ['ml', 5],
        'colher_sobremesa' => ['ml', 10],
        'colher_sopa' => ['ml', 15],
        'xicara' => ['ml', 240],
        'copo' => ['ml', 200],
        'pitada' => ['ml', 0.5],
        // Contáveis.
        'dente' => ['un', 1],
        'fatia' => ['un', 1],
        'maco' => ['un', 1],
        'sache' => ['un', 1],
        'saco' => ['un', 1],
        'bandeja' => ['un', 1],
        'vidro' => ['un', 1],
        'tablete' => ['un', 1],
    ];

    /** Medidas caseiras: servem a ingredientes em ml e em g (aproximação 1 ml ≈ 1 g). */
    private const MEDIDAS_CASEIRAS = ['colher_cha', 'colher_sobremesa', 'colher_sopa', 'xicara', 'copo', 'pitada'];

    /**
     * @return array<string, string>
     */
    public static function rotulos(): array
    {
        return [
            'g' => 'g', 'kg' => 'kg', 'ml' => 'ml', 'l' => 'L', 'un' => 'unidade', 'duzia' => 'dúzia',
            'pacote' => 'pacote', 'lata' => 'lata', 'caixa' => 'caixa', 'pote' => 'pote', 'garrafa' => 'garrafa',
            'colher_cha' => 'colher de chá', 'colher_sobremesa' => 'colher de sobremesa', 'colher_sopa' => 'colher de sopa',
            'xicara' => 'xícara', 'copo' => 'copo', 'pitada' => 'pitada',
            'dente' => 'dente', 'fatia' => 'fatia', 'maco' => 'maço', 'sache' => 'sachê', 'saco' => 'saco', 'bandeja' => 'bandeja',
            'vidro' => 'vidro', 'tablete' => 'tablete',
        ];
    }

    /**
     * Unidades aceitas por unidade base (as medidas caseiras aparecem em g e em ml).
     *
     * @return array<string, array<int, string>>
     */
    public static function porBase(): array
    {
        $grupos = [];
        foreach (array_keys(self::UNIDADES) as $unidade) {
            $grupos[self::UNIDADES[$unidade][0]][] = $unidade;
            if (in_array($unidade, self::MEDIDAS_CASEIRAS, true)) {
                $grupos['g'][] = $unidade;
            }
        }

        return $grupos;
    }

    /**
     * Aceita a chave canônica ou um apelido comum ("quilos", "litro", "dúzias"…); null se não reconhecer.
     */
    public static function normalizar(string $unidade): ?string
    {
        $chave = preg_replace('/[^a-z]/', '', strtolower(\Illuminate\Support\Str::ascii(trim($unidade)))) ?? '';

        $apelidos = [
            'g' => 'g', 'gr' => 'g', 'grama' => 'g', 'gramas' => 'g',
            'kg' => 'kg', 'quilo' => 'kg', 'quilos' => 'kg', 'kilo' => 'kg', 'kilos' => 'kg', 'quilograma' => 'kg', 'quilogramas' => 'kg',
            'ml' => 'ml', 'mililitro' => 'ml', 'mililitros' => 'ml',
            'l' => 'l', 'lt' => 'l', 'litro' => 'l', 'litros' => 'l',
            'un' => 'un', 'und' => 'un', 'unidade' => 'un', 'unidades' => 'un',
            'duzia' => 'duzia', 'duzias' => 'duzia',
            'pacote' => 'pacote', 'pacotes' => 'pacote', 'lata' => 'lata', 'latas' => 'lata',
            'caixa' => 'caixa', 'caixas' => 'caixa', 'pote' => 'pote', 'potes' => 'pote',
            'garrafa' => 'garrafa', 'garrafas' => 'garrafa',
            'colhercha' => 'colher_cha', 'colherdecha' => 'colher_cha', 'colherescha' => 'colher_cha', 'colheresdecha' => 'colher_cha',
            'cc' => 'colher_cha', 'cch' => 'colher_cha', 'colhercafe' => 'colher_cha',
            'colhersobremesa' => 'colher_sobremesa', 'colherdesobremesa' => 'colher_sobremesa', 'colheressobremesa' => 'colher_sobremesa', 'colheresdesobremesa' => 'colher_sobremesa',
            'colhersopa' => 'colher_sopa', 'colherdesopa' => 'colher_sopa', 'colheressopa' => 'colher_sopa', 'colheresdesopa' => 'colher_sopa',
            'cs' => 'colher_sopa', 'csopa' => 'colher_sopa', 'colher' => 'colher_sopa', 'colheres' => 'colher_sopa',
            'xicara' => 'xicara', 'xicaras' => 'xicara', 'xic' => 'xicara', 'xicaradecha' => 'xicara', 'xicarasdecha' => 'xicara',
            'copo' => 'copo', 'copos' => 'copo', 'pitada' => 'pitada', 'pitadas' => 'pitada',
            'dente' => 'dente', 'dentes' => 'dente', 'fatia' => 'fatia', 'fatias' => 'fatia',
            'maco' => 'maco', 'macos' => 'maco', 'sache' => 'sache', 'saches' => 'sache', 'saco' => 'saco', 'sacos' => 'saco',
            'bandeja' => 'bandeja', 'bandejas' => 'bandeja', 'vidro' => 'vidro', 'vidros' => 'vidro', 'tablete' => 'tablete', 'tabletes' => 'tablete',
        ];

        return $apelidos[$chave] ?? null;
    }

    public static function existe(string $unidade): bool
    {
        return isset(self::UNIDADES[$unidade]);
    }

    public static function baseDe(string $unidade): string
    {
        return self::UNIDADES[self::validar($unidade)][0];
    }

    public static function compativel(string $unidade, string $base): bool
    {
        if (! self::existe($unidade)) {
            return false;
        }

        return self::UNIDADES[$unidade][0] === $base || ($base === 'g' && in_array($unidade, self::MEDIDAS_CASEIRAS, true));
    }

    public static function paraBase(float $quantidade, string $unidade): float
    {
        return $quantidade * self::UNIDADES[self::validar($unidade)][1];
    }

    /**
     * Converte uma quantidade na unidade base para uma unidade mais legível (1500 g → 1,5 kg).
     *
     * @return array{0: float, 1: string} [valor, unidade]
     */
    public static function legivel(float $quantidadeBase, string $base): array
    {
        return match ($base) {
            'g' => $quantidadeBase >= 1000 ? [$quantidadeBase / 1000, 'kg'] : [$quantidadeBase, 'g'],
            'ml' => $quantidadeBase >= 1000 ? [$quantidadeBase / 1000, 'l'] : [$quantidadeBase, 'ml'],
            default => [$quantidadeBase, 'un'],
        };
    }

    public static function formatar(float $quantidadeBase, string $base): string
    {
        [$valor, $unidade] = self::legivel($quantidadeBase, $base);

        return rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',').' '.self::rotulos()[$unidade];
    }

    private static function validar(string $unidade): string
    {
        if (! isset(self::UNIDADES[$unidade])) {
            throw new InvalidArgumentException("Unidade desconhecida: {$unidade}");
        }

        return $unidade;
    }
}
