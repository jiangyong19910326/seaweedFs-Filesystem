<?php

namespace SeaweedFS\Laravel;

use Illuminate\Support\Arr;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use SeaweedFS\Filesystem\Mapping\CacheMapper;
use SeaweedFS\Filesystem\Seaweed as SeaweedAdapter;

/**
 * SeaweedFS service provider.
 *
 * @package SeaweedFS\Laravel
 */
class SeaweedFSProvider extends ServiceProvider {

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot() {
        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $configPath = __DIR__ . '/config/seaweedfs.php';

        $this->publishes([
            $configPath => config_path('seaweedfs.php')
        ]);

        $this->mergeConfigFrom($configPath, 'seaweedfs');

        Storage::extend('seaweedfs', function ($app, $config) {
            $client = $app['seaweedfs']->connection(Arr::get($config, 'connection'));
            $mapper = null;
            switch (Arr::get($config, 'mapper', 'database')) {
                case 'cache':
                    $mapper = new CacheMapper(new LaravelCache($app['cache']));
                    break;
                case 'database':
                    $mapper = new DatabaseMapper();
                    break;
            }

            return new Filesystem(new SeaweedAdapter($client, $mapper));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->bind('seaweedfs', function($app) {
            return new SeaweedFSManager($app['config']);
        });

        $this->app->alias('seaweedfs', SeaweedFSManager::class);
    }
}