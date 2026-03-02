<?php

namespace App\Modules\MundosDeMim\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserAttribute extends Model
{
    protected $table = 'mundos_de_mim_user_attributes';

    protected $fillable = [
        'user_id',
        'photo_path',
        'notification_preference',
        'height',     // float
        'weight',     // float
        'body_type',  // string
        'eye_color',  // string
        'hair_type',  // string
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
