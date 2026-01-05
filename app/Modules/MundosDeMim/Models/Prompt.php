<?php
namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    protected $table = 'mundos_de_mim_prompts';
    protected $fillable = ['theme_id', 'prompt_text', 'is_couple_prompt'];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }
}
