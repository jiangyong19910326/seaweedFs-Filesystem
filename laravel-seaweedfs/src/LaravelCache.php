<?php

namespace SeaweedFS\Laravel;

use Illuminate\Contracts\Cache\Repository;
use SeaweedFS\Cache\CacheInterface;

/**
 * CacheInterface using Laravel's Cache system.
 *
 * @package SeaweedFS\Laravel
 */
class LaravelCache implements CacheInterface {

    /**
     * @var Repository The Laravel cache repository.
     */
    private $repository;

    /**
     * Construct a new Laravel backed cache with the specified repository.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository) {
        $this->repository = $repository;
    }

    /**
     * Check if the cache implementation contains the specified key.
     *
     * @param $key
     * @return bool
     */
    public function has($key) {
        return $this->repository->has($key);
    }

    /**
     * Get the specified key from the cache implementation.
     *
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        return $this->repository->get($key, $default);
    }

    /**
     * Set the value in the cache implementation.
     *
     * @param $key
     * @param $value
     * @param int $minutes
     * @return void
     */
    public function put($key, $value, $minutes = 0) {
        if ($minutes == 0) {
            $this->repository->forever($key, $value);
        } else {
            $this->repository->put($key, $value, $minutes);
        }
    }

    /**
     * Remove a value from the cache.
     *
     * @param $key
     * @return void
     */
    public function remove($key) {
        $this->repository->forget($key);
    }
}