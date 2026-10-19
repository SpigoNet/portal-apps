<?php

namespace App\Modules\Pidgey\Models;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Services\AI\ParametrosImagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parâmetros de geração de imagem pré-preenchidos por provedor para as imagens de personagem
 * enviadas pelo Pidgey (independentes dos do MundosDeMim).
 */
class ParametroImagem extends Model
{
    protected $table = 'pidgey_parametros_imagem';

    protected $fillable = ['ai_provedor_id', 'parametros'];

    protected $casts = ['parametros' => 'array'];

    public function provedor(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provedor_id');
    }

    /**
     * Parâmetros do provedor do modelo, prontos para virar `$options` do driver.
     *
     * @return array<string, string|int>
     */
    public static function para(?AiModel $modelo): array
    {
        if (! $modelo || ! $modelo->ai_provedor_id) {
            return [];
        }

        $registro = self::where('ai_provedor_id', $modelo->ai_provedor_id)->first();

        return ParametrosImagem::sanitizar($registro?->parametros ?? []);
    }
}
