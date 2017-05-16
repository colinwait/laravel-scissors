<?php

namespace Colinwait\LaravelScissors;

use Illuminate\Support\ServiceProvider;

class ScissorProvider extends ServiceProvider
{
    protected $config = 'upload-image';

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
        $config = $this->config;
        $this->app->singleton('scissors', function () use ($config) {
            return $this->getEntity($config);
        });
    }

    /**
     * get entity by driver
     *
     * @param $config
     *
     * @return QiniuEntity|ScissorEntity
     */
    private function getEntity($config)
    {
        $driver = $this->app['config']->get($config)['driver'];
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
