<?php

namespace App\Modules\TreeTask\Models;

use Illuminate\Database\Eloquent\Model;

class LorePrompt extends Model
{
    protected $table = 'treetask_lore_prompts';

    protected $fillable = [
        'universo',
        'prompt_personagem',
        'ativo'
    ];
}
