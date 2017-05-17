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
        $this->setupMigrations();
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
     * setup migrations
     */
    protected function setupMigrations()
    {
        $source = realpath(__DIR__ . '/../database/migrations/');
        $this->publishes([$source => database_path('migrations')], 'migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('scissors', function () {
            return $this->getEntity();
        });
    }

    /**
     * get entity by driver
     *
     * @return QiniuEntity|ScissorEntity
     */
    private function getEntity()
    {
        $config = $this->app['config']->get($this->config);
        $driver = $config['driver'];
        switch ($driver) {
            case 'qiniu' :
                return new QiniuEntity($config);
                break;
            default :
                return new ScissorEntity($config);
                break;
        }
    }
}
