<?php

namespace SeaweedFS\Laravel;

use DB;
use SeaweedFS\Filesystem\Mapping\Mapper;

/**
 * A SewaeedFS Mapper using Laravel's Database class
 *
 * @package SeaweedFS\Laravel
 */
class DatabaseMapper implements Mapper {

    /**
     * Store a path to a seaweedfs file id
     *
     * @param $path
     * @param $fileId
     * @param $mimeType
     * @param $size
     * @return mixed
     */
    public function store($path, $fileId, $mimeType, $size) {
        return DB::table('seaweedfs_mappings')->insert([
            'path' => $path,
            'fid' => $fileId,
            'mimeType' => $mimeType,
            'size' => $size
        ]);
    }

     /**
     * Store a directory to a seaweedfs file id
     *
     * @param $directory
     * @param $fileId
     * @param $mimeType
     * @param $size
     * @return mixed
     */
    public function storeDirectory($directory,$fileId)
    {
        return DB::table('seaweedfs_mappings')->insert([
            'path' => $directory,
            'fid' =>$fileId,
        ]);
    }

    /**
     * Map a path to a seaweedfs file id
     *
     * @param $path
     * @return mixed
     */
    public function get($path) {
        $result = DB::table('seaweedfs_mappings')->select('fid', 'mimeType', 'size')->where('path', $path)->first();

        return $result ? get_object_vars($result) : null;
    }

    /**
     * Remove a path mapping.
     *
     * @param $path
     * @return mixed
     */
    public function remove($path) {
        DB::table('seaweedfs_mappings')->where('path', $path)->delete();
    }
}