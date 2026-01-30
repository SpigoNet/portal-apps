<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalAppUser extends Model
{
    public $timestamps = false;

    protected $table = 'portal_app_user';
    protected $fillable = [
        'portal_app_id',
        'user_id',
        'role',
    ];

    public function portalApp(): BelongsTo
    {
        return $this->belongsTo(PortalApp::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
