<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DspaceFormMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'map_type',
        'map_key',
        'submission_name',
    ];
}

