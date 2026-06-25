<?php

namespace App\Modules\Alfred\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'alfred_personas';

    protected $fillable = [
        'name',
        'slug',
        'whatsapp_group_jid',
        'personality',
        'metadata',
        'active',
    ];

    protected $casts = [
        'personality' => 'array',
        'metadata' => 'array',
        'active' => 'boolean',
    ];
}
