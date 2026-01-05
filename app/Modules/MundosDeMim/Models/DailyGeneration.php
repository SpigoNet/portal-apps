<?php
namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DailyGeneration extends Model
{
    protected $table = 'mundos_de_mim_daily_generations';

    protected $fillable = [
        'user_id',
        'theme_id',
        'prompt_id',
        'image_url',
        'final_prompt_used',
        'reference_date'
    ];

    protected $casts = [
        'reference_date' => 'date',
    ];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }
}
