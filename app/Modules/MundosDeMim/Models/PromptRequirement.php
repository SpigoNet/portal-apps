<?php
namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;

class PromptRequirement extends Model
{
    protected $table = 'mundos_de_mim_prompt_requirements';

    protected $fillable = [
        'prompt_id',
        'requirement_key',
        'requirement_value',
        'operator'
    ];

    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }
}
