<?php

namespace App\Modules\Yomi;

use App\Modules\Yomi\Contracts\MangaProvider;
use App\Modules\Yomi\Models\Criador;
use App\Modules\Yomi\Models\Manga;
use App\Modules\Yomi\Models\Personagem;
use App\Modules\Yomi\Providers\JikanProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class YomiServiceProvider extends ServiceProvider
{
    protected $namespace = 'Yomi';

    public function boot(): void
    {
        Relation::morphMap([
            'manga' => Manga::class,
            'personagem' => Personagem::class,
            'criador' => Criador::class,
        ]);

        if (is_dir(__DIR__.'/resources/views')) {
            $this->loadViewsFrom(__DIR__.'/resources/views', $this->namespace);
            Blade::anonymousComponentPath(__DIR__.'/resources/views/components', 'yomi');
        }

        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/routes.php');

        Route::group([], __DIR__.'/api.php');
    }

    public function register(): void
    {
        $this->app->bind(MangaProvider::class, JikanProvider::class);

        $this->app->singleton(\App\Modules\Yomi\Services\MangaService::class);
        $this->app->singleton(\App\Modules\Yomi\Services\MangaSyncService::class);
        $this->app->singleton(\App\Modules\Yomi\Services\SyncManager::class);
        $this->app->singleton(\App\Modules\Yomi\Services\ProgressService::class);
        $this->app->singleton(\App\Modules\Yomi\Services\MediaService::class);
        $this->app->singleton(\App\Modules\Yomi\Services\YomiSettingsService::class);
    }
}
