<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;

class YomiSetting extends Model
{
    protected $table = 'yomi_settings';

    protected $guarded = [];

    protected $casts = [
        'value' => 'array',
    ];
}
