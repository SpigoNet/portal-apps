<?php

namespace App\Modules\MundosDeMim\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DailyGeneration extends Model
{
    protected $table = 'mundos_de_mim_daily_generations';

    protected $fillable = [
        'user_id',
        'theme_id',
        'prompt_id',
        'image_url',
        'final_prompt_used',
        'reference_date',
        'rating',
    ];

    protected $casts = [
        'reference_date' => 'date',
    ];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
