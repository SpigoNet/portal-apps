<?php
namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $table = 'mundos_de_mim_themes';
    protected $fillable = ['name', 'slug', 'age_rating', 'is_seasonal', 'starts_at', 'ends_at'];

    public function prompts()
    {
        return $this->hasMany(Prompt::class);
    }
}
