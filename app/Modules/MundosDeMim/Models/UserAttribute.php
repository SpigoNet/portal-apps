<?php

namespace App\Modules\MundosDeMim\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserAttribute extends Model
{
    protected $table = 'mundos_de_mim_user_attributes';

    protected $fillable = [
        'user_id',
        'photo_path',
        'height',     // float [cite: 26]
        'weight',     // float [cite: 27]
        'body_type',  // string [cite: 28]
        'eye_color',  // string [cite: 29]
        'hair_type',  // string [cite: 29]
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
