<?php
namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeExample extends Model
{
    protected $table = 'mundos_de_mim_theme_examples';
    protected $fillable = ['theme_id', 'prompt_id', 'image_path'];

    public function prompt()
    {
        return $this->belongsTo(Prompt::class, 'prompt_id');
    }
}
