<?php

namespace App\Modules\TreeTask\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserAvatar extends Model
{
    protected $table = 'treetask_user_avatars';

    protected $fillable = [
        'user_id',
        'nome_pet',
        'tipo_pet',
        'nivel',
        'energia',
        'ultimo_checkin'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
