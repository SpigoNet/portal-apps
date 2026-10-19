<?php

namespace App\Modules\MundosDeMim\Models;

use App\Models\AiProvider;
use App\Services\AI\ParametrosImagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parâmetros de geração de imagem pré-preenchidos por provedor.
 * Toda imagem gerada pelo módulo para esse provedor leva estes parâmetros junto.
 */
class ParametroImagem extends Model
{
    protected $table = 'mundos_de_mim_parametros_imagem';

    protected $fillable = ['ai_provedor_id', 'parametros'];

    protected $casts = ['parametros' => 'array'];

    /** Opções de lista aceitas por parâmetro (espelham o gateway OpenAI-compatível). */
    public const OPCOES = ParametrosImagem::OPCOES;

    public function provedor(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provedor_id');
    }

    /**
     * Mantém só parâmetros conhecidos e válidos; vazio/inválido é descartado.
     *
     * @param  array<string, mixed>  $entrada
     * @return array<string, string|int>
     */
    public static function sanitizar(array $entrada): array
    {
        return ParametrosImagem::sanitizar($entrada);
    }
}
