<?php

namespace SeaweedFS;

use GuzzleHttp\Client;
use SeaweedFS\Cache\CacheInterface;
use SeaweedFS\Model\File;
use SeaweedFS\Model\FileMeta;
use SeaweedFS\Model\Volume;

class SeaweedFS
{
    const DIR_ASSIGN = '/dir/assign';
    const DIR_LOOKUP = '/dir/lookup';

    /**
     * @var string A master server address.
     */
    protected $master;

    /**
     * @var string The API and File access scheme.
     */
    protected $scheme;

    /**
     * @var Client The preconfigured Guzzle client.
     */
    protected $client;

    /**
     * @var CacheInterface The cache interface for meta/volumes
     */
    protected $cache;

    /**
     * Construct a new SeaweedFS client.
     *
     * @param $master
     * @param string $scheme
     * @param CacheInterface|null $cache
     */
    public function __construct($master, $filer, $scheme = 'http', $cache = null)
    {
        $this->master = $master;
        $this->filer = $filer;
        $this->scheme = $scheme;
        $this->cache = $cache;

        $this->client = new Client();
    }

    /**
     * Get a volume and file id from the master server.
     *
     * @param int  $count
     * @param string|null $collection
     * @param string|null $replication
     * @param string|null $dataCenter
     * @return File
     * @throws SeaweedFSException
     * @see https://github.com/chrislusf/seaweedfs/wiki/Master-Server-API#assign-a-file-key
     */
    public function assign($count = 1, $collection = null, $replication = null, $dataCenter = null)
    {
        $assignProperties = ['count' => $count];

        if (!is_null($collection)) {
            $assignProperties['collection'] = $collection;
        }

        if (!is_null($replication)) {
            $assignProperties['replication'] = $replication;
        }

        if (!is_null($dataCenter)) {
            $assignProperties['dataCenter'] = $dataCenter;
        }

        $res = $this->client->get($this->buildMasterUrl(self::DIR_ASSIGN), [
            'query' => $assignProperties
        ]);

        if ($res->getStatusCode() != 200) {
            throw new SeaweedFSException('Unexpected response when assigning file: ' . $res->getStatusCode());
        }

        $body = json_decode((string) $res->getBody());

        return new File($body, $this->scheme);
    }

    /**
     * Lookup a volume or file on the master server.
     *
     * @param $id
     * @return Volume
     * @throws SeaweedFSException
     */
    public function lookup($id)
    {
        if ($pos = strpos($id, ',')) {
            $id = substr($id, 0, $pos);
        }

        $cacheKey = 'volume_' . $id;

        if ($this->cache && $this->cache->has($cacheKey)) {
            $val = $this->cache->get($cacheKey);

            if (!$val instanceof Volume) {
                $val = new Volume($val);
            }

            return $val;
        }

        $res = $this->client->get($this->buildMasterUrl(self::DIR_LOOKUP), [
            'query' => ['volumeId' => $id]
        ]);

        if ($res->getStatusCode() != 200) {
            throw new SeaweedFSException('Unexpected response when looking up volume: ' . $res->getStatusCode());
        }

        $body = json_decode((string) $res->getBody());

        $volume = new Volume($body);

        if ($this->cache) {
            $this->cache->put($cacheKey, $volume);
        }

        return $volume;
    }

    /**
     * Upload/update a file.
     *
     * If a file (provided by assign) does not exist, one will be created.
     *
     * @param resource|string $data The file data, either a string or resource.
     * @param string $filename
     * @param File|null $file A file object to update.
     * @return File
     * @throws SeaweedFSException
     */
    public function upload($path, $data, $filename = 'file.txt', $file = null)
    {
        $dir = glz($this->buildFileUrl($path),[]);
        if($dir)
        {
            //创建文件
            // dd($path,$filename);

            $res = $this->client->post($this->buildFilerUrl($path), [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'filename' => $filename,
                        'contents' => $data,
                    ]
                ],
    
            ]);
        } else {
            $path = $path.'?op=append'; //修改文件 
            $res = $this->client->post($this->buildFileUrl($path), [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'filename' => $filename,
                        'contents' => $data,
                    ]
                ],
    
            ]);
        }
        if ($res->getStatusCode() == 201) {
            return true;
        } else {
            return false;
        }
    }

    public function directory($diretory, $path = null)
    {

        $res = $this->client->post($this->buildFilerUrl($diretory), []);
        if ($res->getStatusCode() != 201) {
            throw new SeaweedFSException('Unexpected response when storing path: ' . $res->getStatusCode());
        } else {
            return true;
        }
    }

    //判断是否存在目录，有救返回数组，没有酒返回false;
    public function hasDir($diretory, $path = null)
    {
        try {
            $res = glz($this->buildFilerUrl($diretory), [], "GET", ['Accept' => 'application/json']);
            if ($res) {
                //路径存在
                return $res;
            } else {
                return false;
            }
        } catch (SeaweedFSException $e) {
            return false;
        }
    }

    /**
     * Fetch a file from a volume.
     *
     * @param $fid
     * @param null $ext
     * @return resource
     * @throws SeaweedFSException
     */
    public function get($fid, $ext = null)
    {
        $res = glz($this->buildFilerUrl($fid), []);
        if ($res) {
            $new = [];
            $new['path'] = $res['Path'];
            unset($res['Path']);
            $new = array_merge($new,$res);
            return $new;
        } else {
            $file =  @fopen($this->buildFileUrl($fid),'r');
            if($file)
            {
                return $file;
            } else {
                return false;
            }
        }
    }

    public function getUrl($path)
    {
        $res = $this->client->get($this->buildFileUrl($path));
        if($res->getStatusCode() != 200)
        {
            return false;
        } else {
            return $path;
        }
    }

    /**
     * Check if the specified file exists.
     *
     * @param $fid
     * @return bool
     */
    public function has($fid)
    {
        try {
            return $this->meta($fid) != null;
        } catch (SeaweedFSException $e) {
            return false;
        }
    }

    /**
     * Get a file's information (type, size, filename)
     *
     * @param $fid
     * @return FileMeta
     * @throws SeaweedFSException
     */
    public function meta($fid)
    { 
        //这是目录或者文件
        $res = glz($this->buildFilerUrl($fid), []);
        if ($res) {
            return $res;
        } else {
            $fid = rtrim($fid);
            $file = $this->get($fid);
          
            return $file;
        
        }
    }

    /**
     * Delete the specified file.
     *
     * @param $fid
     * @return bool
     * @throws SeaweedFSException
     */
    public function delete($fid)
    {
        $res = $this->client->delete($this->buildFilerUrl($fid));
        if($res->getStatusCode() == 202)
        {
            return true;
        } else {
            return false;
        }
    }

    public function deleteDirectory($dir)
    {
        $res = $this->client->delete($this->buildFilerUrl($dir).'?recursive=true');
        if($res->getStatusCode() == 202)
        {
            return true;
        } else {
            return false;
        }
    }

    //重命名整个文件
    public function rename($path,$newpath)
    {
        return  $this->client->post(rtrim($this->buildFilerUrl($newpath),'/').'?mv.from=/'.$path);
    }

    public function move($path,$newpath)
    {
        // dd($this->buildFilerUrl($newpath).'?mv.from=/'.$path);
        return $this->client->post($this->buildFilerUrl($newpath).'?mv.from=/'.$path);
    }

    public function copy($path,$newpath)
    {
       //获取文件
       $res = $this->client->get($this->buildFileUrl($path));
       if($res->getStatusCode() == 200)
       {
           $filename = substr($newpath,(strripos($newpath,"/")+1-strlen($newpath)));
           $path=substr($newpath,0,strripos($newpath,"/"));
            //上传目录中的文件
            $result = $this->client->post($this->buildFilerUrl($path), [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'filename' => $filename,
                        'contents' => $res->getBody(),
                    ]
                ],

            ]);
            if($result->getStatusCode() == 200)
            {
                return true;
            } else {
                return false;
            }
       }
       
    }

    /**
     * Build a URL to a master server path.
     *
     * @param $path
     * @return string
     */
    public function buildMasterUrl($path = null)
    {
        return sprintf('%s://%s/%s', $this->scheme, $this->master, $path ? ltrim($path, '/') : '');
    }

    /**
     * Build a URL to a volume server path.
     *
     * @param $host
     * @param null $path
     * @return string
     */
    public function buildVolumeUrl($host, $path = null)
    {
        return sprintf('%s://%s/%s', $this->scheme, $host, $path);
    }

    public function buildFilerUrl($path = null)
    {
        return sprintf('%s://%s/%s', $this->scheme, $this->filer, $path ? $path . '/' : '');
    }

    public function buildFileUrl($path = null)
    {
        return sprintf('%s://%s/%s', $this->scheme, $this->filer, $path ? $path  : '');
    }
}
