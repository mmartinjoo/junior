<?php

namespace Mmartinjoo\Junior;

use Illuminate\Support\ServiceProvider;
use Mmartinjoo\Junior\Commands\JuniorCommand;

class JuniorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/junior.php' => config_path('junior.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'junior');
    }

    public function register()
    {
        $this->publishes([
            __DIR__ . '/../config/junior.php' => config_path('junior.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                JuniorCommand::class,
            ]);
        }
    }
}
