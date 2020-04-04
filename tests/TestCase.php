<?php

namespace Naoray\LaravelFactoryPrefill\Tests;

use Dotenv\Dotenv;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Naoray\LaravelFactoryPrefill\LaravelFactoryPrefillServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        if (!file_exists(__DIR__ . '/../.env')) {
            return;
        }

        $dotenv = Dotenv::create(__DIR__ . '/..');
        $dotenv->load();

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => env('DB_DATABASE', 'laravel_factory_prefill'),
            'username' => env('DB_USERNAME', 'username'),
            'password' => env('DB_PASSWORD', 'password'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
    }
}
