<?php

namespace SeaweedFS\Filesystem;

use function GuzzleHttp\Psr7\mimetype_from_filename;

use Faker\Provider\Base;
use GuzzleHttp\Psr7\MimeType;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use SeaweedFS\SeaweedFS;
use SeaweedFS\Filesystem\Mapping\Mapper;
use SeaweedFS\SeaweedFSException;

class Seaweed extends AbstractAdapter implements CanOverwriteFiles {
    use NotSupportingVisibilityTrait;

    /**
     * @var SeaweedFS The SeaweedFS client
     */
    private $client;

    /**
     * @var Mapper The filesystem mapper
     */
    private $mapper;

    /**
     * Construct a new Adapter for SeaweedFS with the given client and mapper.
     *
     * @param SeaweedFS $client
     * @param Mapper $mapper
     */
    public function __construct(SeaweedFS $client, Mapper $mapper) {
        $this->client = $client;
        $this->mapper = $mapper;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $file = null;
        //这传过来的路径需要解析
        $filename = basename($path);
        $path=substr($path,0,strripos($path,"/"));
        $file = $this->client->upload($path,$contents,$filename,$file);
        if($file)
        {
            return $file;
        } else {
            return false;
        }
    }


     /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config) {
        try {
            $dir = $this->client->directory($dirname,[]);
            return $dir;
        } catch (SeaweedFSException $e) {
            return false;
        }
        // throw new LogicException(get_class($this) . ' does not support directory creation. Path: ' . $dirname);
    }

    function std_class_object_to_array($stdclassobject)
    {
        $_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
        foreach ($_array as $key => $value) {
            $value = (is_array($value) || is_object($value)) ? std_class_object_to_array($value) : $value;
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config) {
        return $this->write($path, $resource, $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config) {
        $config->update = true;
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config) {
        return $this->write($path, $resource, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath) {
        $res = $this->client->rename($path,$newpath);
    }


    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function move($path, $newpath) {
        $res = $this->client->move($path,$newpath);
    }

    //复制
    public function copy($path,$newpath)
    {
        $res = $this->client->copy($path,$newpath);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path) {
        return $this->client->delete($path);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname) {
        return $this->client->deleteDirectory($dirname);
    }

   

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path) {
        $mapping = $this->mapper->get($path);
        if (!$mapping) {
            return $this->client->has($path);
        }

        return $this->client->has($mapping['fid']);
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path) {
        $file = $this->client->get($path);
        if(!$file)
        {
            return false;
        } else {
            return [
                'contents' => stream_get_contents($file)
            ];
        }
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path) {
        $file = $this->client->get($path);
        // dd($mapping);
        if(!$file)
        {
            return false;
        } else {
            return [
                'stream' => $file,
            ];
        }
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false) {
        $dir = glz($this->client->buildFilerUrl($directory),[]);
 
        if($dir)
        {
            $dir['path'] = $dir['Path'];
            if(isset($dir['Entries']))
            {
                foreach($dir['Entries'] as $k=>$v)
                {
                    $dir['Entries'][$k]['path'] = ltrim($v['FullPath'],'/');
                    if(isset($v['Mime']) && $v['FileSize']){
                        $dir['Entries'][$k]['type'] = 'file';
                    } else {
                        $dir['Entries'][$k]['type'] = 'dir';
                    }
                }
            } else {
                $dir['Entries'] = [];
            }
        }
        if($dir)
        {
            return $dir['Entries'];
        } else {
            $dir['Entries'] = [];
            return $dir['Entries'];   
        }
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path) {
        return array_merge(['path' => $path],['type' => 'file']);
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path) {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path) {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path) {
        return $this->getMetadata($path);
    }

    /**
     * Get the public URL of a path.
     *
     * @param $path
     * @return string|bool
     */
    public function getUrl($path) {
        $res = $this->client->getUrl($path);
        if($res)
        {
            return $res;
        } else {
            return false;
        }
        // $mapping = $this->mapper->get($path);

        // if (!$mapping) {
        //     return false;
        // }

        // try {
        //     $volume = $this->client->lookup($mapping['fid']);

        //     return $this->client->buildVolumeUrl($volume->getPublicUrl(), $mapping['fid']);
        // } catch (SeaweedFSException $e) {
        //     return false;
        // }
    }
}