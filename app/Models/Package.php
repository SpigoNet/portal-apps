<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public function portalApps()
    {
        return $this->hasMany(\App\Models\PortalApp::class);
    }

}
