<?php
namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Theme extends Model
{
    protected $table = 'mundos_de_mim_themes';

    protected $fillable = [
        'name', 'slug', 'age_rating', 'is_seasonal', 'starts_at', 'ends_at',
        'example_input_path', 'example_input_description' // Novos campos
    ];

    // Relação com Exemplos de Resultado ("Depois")
    public function examples()
    {
        return $this->hasMany(ThemeExample::class);
    }

    // Relação Many-to-Many com Usuários (Preferências)
    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'mundos_de_mim_user_themes')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    public function prompts()
    {
        return $this->hasMany(Prompt::class, 'theme_id');
    }
}
