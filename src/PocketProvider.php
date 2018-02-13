<?php

namespace Colinwait\LaravelPockets;

use Illuminate\Support\ServiceProvider;

class PocketProvider extends ServiceProvider
{
    protected $config = 'pocket';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * setup config
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/' . $this->config . '.php');
        $this->publishes([$source => config_path($this->config . '.php')]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pocket', function () {
            return new PocketEntity();
        });
    }
}
