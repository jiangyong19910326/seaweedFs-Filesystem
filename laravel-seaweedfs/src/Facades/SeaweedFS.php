<?php

namespace SeaweedFS\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * SeaweedFS Manager facade
 *
 * @package SeaweedFS\Laravel\Facades
 */
class SeaweedFS extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'seaweedfs';
    }
}