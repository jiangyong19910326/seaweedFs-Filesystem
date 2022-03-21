<?php

namespace SeaweedFS\Cache;

/**
 * A basic cache that uses the Filesystem for storage.
 *
 * @package SeaweedFS\Cache
 */
class FileCache implements CacheInterface {
    /**
     * @var string Base directory.
     */
    private $baseDir;

    /**
     * Construct a new file backed cache with the specified base directory.
     *
     * @param $baseDir
     */
    public function __construct($baseDir) {
        $this->baseDir = $baseDir;
    }

    /**
     * Check if the cache implementation contains the specified key.
     *
     * @param $key
     * @return bool
     */
    public function has($key) {
        return file_exists($this->baseDir . '/' . md5($key));
    }

    /**
     * Get the specified key from the cache implementation.
     *
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        if (!$this->has($key)) {
            return $default;
        }

        return @unserialize(file_get_contents($this->baseDir . '/' . md5($key))) ?: $default;
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
        file_put_contents($this->baseDir . '/' . md5($key), serialize($value));
    }

    /**
     * Remove a value from the cache.
     *
     * @param $key
     * @return void
     */
    public function remove($key) {
        return unlink($this->baseDir . '/' . md5($key));
    }
}