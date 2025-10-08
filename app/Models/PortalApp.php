<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalApp extends Model
{
    public function package()
    {
        return $this->belongsTo(\App\Models\Package::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'portal_app_user');
    }
}
