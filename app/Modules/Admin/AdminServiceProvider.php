<?php
namespace App\Modules\Admin;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot()
    {

        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');

        // O namespace 'Admin' permite usar views como 'Admin::apps.index'
        $this->loadViewsFrom(__DIR__.'/resources/views', 'Admin');
    }
}
