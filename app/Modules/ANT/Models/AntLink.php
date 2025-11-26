<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntLink extends Model
{
    protected $table = 'ant_links';
    protected $fillable = ['grupo', 'nome', 'link', 'is_video'];

    protected $casts = [
        'is_video' => 'boolean',
    ];
}
