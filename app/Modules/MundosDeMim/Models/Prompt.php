<?php
namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    protected $table = 'mundos_de_mim_prompts';
    // Removemos 'is_couple_prompt' do fillable
    protected $fillable = ['theme_id', 'prompt_text'];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    // Nova relação
    public function requirements()
    {
        return $this->hasMany(PromptRequirement::class);
    }
}
