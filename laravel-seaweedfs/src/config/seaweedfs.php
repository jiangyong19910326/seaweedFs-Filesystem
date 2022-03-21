<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | This option controls the default content connection that gets used while
    | using this storage library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('SEAWEEDFS_CONNECTION', 'local'),

    /*
    |--------------------------------------------------------------------------
    | SeaweedFS Masters
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the seaweedfs connections for your
    | application. 'master' is the hostname:port of the master server,
    | while scheme is the scheme it's accessed over (http/https).
    |
    */

    'connections' => [

        'local' => [
            'master' => '127.0.0.1:9333',
            'scheme' => 'http',
            'cache' => 'default'
        ],

    ]
];