<?php

namespace Naoray\LaravelFactoryPrefill;

use Illuminate\Support\ServiceProvider;
use Naoray\LaravelFactoryPrefill\Commands\PrefillFactory;

class LaravelFactoryPrefillServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->commands([PrefillFactory::class]);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'prefill-factory-helper');
    }

    /**
     * Register services.
     */
    public function register()
    {
    }
}
