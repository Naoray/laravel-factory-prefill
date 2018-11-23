<?php

namespace Naoray\LaravelFactoryPrefill;

use Illuminate\Support\ServiceProvider;
use Naoray\LaravelFactoryPrefill\Commands\PrefillFactory;

class LaravelFactoryPrefillServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([PrefillFactory::class]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
