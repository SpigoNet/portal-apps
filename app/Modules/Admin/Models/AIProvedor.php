<?php
namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class AIProvedor extends Model
{
    protected $table = 'ai_provedores';
    protected $fillable = ['nome', 'url_json_modelos', 'default_input_types', 'default_output_types'];
    protected $casts = [
        'default_input_types' => 'array',
        'default_output_types' => 'array'
    ];

    public function modelos()
    {
        return $this->hasMany(AIModelo::class, 'ai_provedor_id');
    }
}
