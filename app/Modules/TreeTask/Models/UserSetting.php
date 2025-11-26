<?php

namespace App\Modules\TreeTask\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserSetting extends Model
{
    protected $table = 'treetask_user_settings';

    protected $fillable = [
        'user_id',
        'focus_mode_sound_type',
        'use_analog_timer',
        'palette_style',
        'enable_haptic_feedback',
        'enable_streak_repair'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
