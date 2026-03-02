<?php
namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class AIModeloPadrao extends Model
{
    protected $table = 'ai_modelos_padrao';
    protected $fillable = ['input_type', 'output_type', 'ai_modelo_id'];

    public function modelo()
    {
        return $this->belongsTo(AIModelo::class, 'ai_modelo_id');
    }

    public static function getPadrao(string $inputType, string $outputType): ?AIModelo
    {
        $padrao = self::where('input_type', $inputType)
            ->where('output_type', $outputType)
            ->first();

        return $padrao?->modelo;
    }
}
