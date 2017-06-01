<?php

namespace Colinwait\LaravelScissors;

use Illuminate\Support\ServiceProvider;

class ScissorProvider extends ServiceProvider
{
    protected $config = 'scissor';

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
        $this->app->singleton('scissor', function () {
            return new ScissorEntity();
        });
    }
}
