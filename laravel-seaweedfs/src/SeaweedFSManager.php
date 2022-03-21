<?php

namespace SeaweedFS\Laravel;

use GrahamCampbell\Manager\AbstractManager;
use Illuminate\Support\Arr;
use SeaweedFS\Cache\FileCache;
use SeaweedFS\SeaweedFS;

/**
 * SeaweedFS Manager Class, extending GrahamCampbell's Manager package.
 *
 * @package SeaweedFS\Laravel
 */
class SeaweedFSManager extends AbstractManager {

    /**
     * Create the connection instance.
     *
     * @param array $config
     *
     * @return object
     */
    protected function createConnection(array $config) {
        $cache = null;
        switch (Arr::get($config, 'cache', 'laravel')) {
            case 'file':
                $cache = new FileCache(Arr::get($config, 'root', storage_path('seaweedfs')));
                break;
            case 'default':
            case 'laravel':
                $cache = new LaravelCache(app('cache')->store(Arr::get($config, 'cache_store')));
                break;
        }

        return new SeaweedFS($config['master'], $config['filer'],Arr::get($config, 'scheme', 'http'), $cache);
    }

    /**
     * Get the configuration name.
     *
     * @return string
     */
    protected function getConfigName() {
        return 'seaweedfs';
    }
}