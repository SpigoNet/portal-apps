<?php

namespace App\Services;

use App\Models\PortalApp;
use Illuminate\Support\Facades\Cache;

class ModuleService
{
    public $appId;
    public function __construct($appId)
    {
        $this->appId = $appId;
        if (!Cache::has('current_app_id')) {
            Cache::put('current_app_id', $appId);
            $a = PortalApp::query()
                ->where('id', $appId)
                ->first()->toArray();
            Cache::put('current_app', $a);
        } else {
            if (Cache::get('current_app_id') != $appId) {
                Cache::put('current_app_id', $appId);
                $a = PortalApp::query()
                    ->where('id', $appId)
                    ->first()->toArray();
                Cache::put('current_app', $a);
            }
        }
    }
    public function getCurrentApp()
    {
        return Cache::get('current_app');
    }
}
