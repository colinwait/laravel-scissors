<?php


namespace Colinwait\LaravelScissors;

use Carbon\Carbon;
use Intervention\Image\Facades\Image;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class QiniuEntity implements ScissorInterface
{
    use StorageTrait;

    protected $config;

    protected $source;

    protected $key;

    public function __construct($config)
    {
        $this->config = $config['qiniu'];
    }

    public function putFile($path, $Key = null)
    {
        $this->source = $path;
        $this->key    = $Key;

        $img = $this->makeImage();
        $key = $this->getKeyByData();

        if (is_null($key)) {
            throw new \Exception('Image Upload Failed');
        }

        $stat = $this->getStatByKey($key);

        $info = [
            'key'      => $key,
            'filename' => $key,
            'width'    => $img->width(),
            'height'   => $img->height(),
            'filesize' => $img->filesize() ?: $stat[0]['fsize'],
            'mime'     => $img->mime(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        return $this->storage($info);
    }

    public function put($data, $key = null)
    {
        return $this->putFile($data, $key);
    }

    public function fetch($url, $Key = null)
    {
        $this->source = $url;
        $this->key    = $Key;

        $img = $this->makeImage();
        $key = $this->getKeyByData();

        if (is_null($key)) {
            throw new \Exception('Image Upload Failed');
        }

        $stat = $this->getKeyByUrl();

        $info = [
            'key'        => $key,
            'filename'   => $key,
            'width'      => $img->width(),
            'height'     => $img->height(),
            'filesize'   => $img->filesize() ?: $stat[0]['fsize'],
            'mime'       => $img->mime(),
            'origin_url' => $url,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        return $this->storage($info);
    }

    public function get($key)
    {
        return $this->show($key);
    }

    public function delete($key)
    {
        $bucketManager = new BucketManager($this->getAuth());

        $res = $bucketManager->delete($this->config['bucket'], $key);

        if (is_null($res)) {
            return $this->destroy($key);
        }

        throw new \Exception($res->message());
    }

    private function getAuth()
    {
        $auth = new Auth($this->config['accessKey'], $this->config['secretKey']);

        return $auth;
    }

    private function getUpToken()
    {
        $auth = $this->getAuth();

        $token = $auth->uploadToken($this->config['bucket']);

        return $token;
    }

    private function makeImage()
    {
        return Image::make($this->source);
    }

    private function getKeyByData()
    {
        $qiniuManager = new UploadManager();

        $res = $qiniuManager->putFile($this->getUpToken(), $this->key, $this->source);

        return isset($res[0]['key']) ? $res[0]['key'] : null;
    }

    private function getKeyByUrl()
    {
        $bucketManager = new BucketManager($this->getAuth());
        $res           = $bucketManager->fetch($this->source, $this->config['bucket'], $this->key);

        return isset($res[0]['key']) ? $res[0]['key'] : null;
    }

    private function getStatByKey($key)
    {
        $bucketManager = new BucketManager($this->getAuth());

        return $bucketManager->stat($this->config['bucket'], $key);
    }
}