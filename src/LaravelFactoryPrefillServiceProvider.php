<?php

namespace Naoray\LaravelFactoryPrefill;

use Illuminate\Support\ServiceProvider;
use Naoray\LaravelFactoryPrefill\Commands\PrefillAll;
use Naoray\LaravelFactoryPrefill\Commands\PrefillFactory;

class LaravelFactoryPrefillServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PrefillFactory::class,
                PrefillAll::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'prefill-factory-helper');
    }

    /**
     * Register services.
     */
    public function register()
    {
    }
}
