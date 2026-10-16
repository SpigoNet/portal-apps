<?php

namespace App\Modules\Cardapio\Support;

class TipoRefeicao
{
    public const CAFE_DA_MANHA = 'cafe_da_manha';

    public const LANCHE_DA_MANHA = 'lanche_da_manha';

    public const ALMOCO = 'almoco';

    public const LANCHE_DA_TARDE = 'lanche_da_tarde';

    public const JANTAR = 'jantar';

    public const CEIA = 'ceia';

    public const LANCHEIRA = 'lancheira';

    /**
     * @return array<string, string>
     */
    public static function rotulos(): array
    {
        return [
            self::CAFE_DA_MANHA => 'Café da manhã',
            self::LANCHE_DA_MANHA => 'Lanche da manhã',
            self::ALMOCO => 'Almoço',
            self::LANCHE_DA_TARDE => 'Lanche da tarde',
            self::JANTAR => 'Jantar',
            self::CEIA => 'Ceia',
            self::LANCHEIRA => 'Lancheira escolar',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function padrao(): array
    {
        return [self::CAFE_DA_MANHA, self::ALMOCO, self::LANCHE_DA_TARDE, self::JANTAR, self::LANCHEIRA];
    }

    /**
     * Dias da semana ISO (1 = segunda ... 7 = domingo).
     *
     * @return array<int, string>
     */
    public static function dias(): array
    {
        return [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
    }
}
