<?php
namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class AIModelo extends Model
{
    protected $table = 'ai_modelos';
    protected $fillable = [
        'ai_provedor_id', 'modelo_id_externo', 'nome',
        'descricao', 'input_types', 'output_types', 'pricing', 'raw_data', 'is_active'
    ];
    protected $casts = [
        'input_types' => 'array',
        'output_types' => 'array',
        'pricing' => 'array',
        'raw_data' => 'array',
        'is_active' => 'boolean'
    ];

    public function provedor()
    {
        return $this->belongsTo(AIProvedor::class, 'ai_provedor_id');
    }

    public function e_padrao()
    {
        return $this->hasMany(AIModeloPadrao::class, 'ai_modelo_id');
    }
}
