<?php


namespace Colinwait\LaravelScissors;


use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class QiniuEntity implements ScissorInterface
{
    protected $config;

    public function __construct($config)
    {
        $this->config = config($config . '.qiniu');
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

    public function putFile($path, $Key = null)
    {
        $qiniuManager = new UploadManager();

        $res = $qiniuManager->putFile($this->getUpToken(), $Key, $path);

        return isset($res[0]['key']) ? $res[0]['key'] : '';
    }

    public function fetch($url, $key = null)
    {
        $bucketManager = new BucketManager($this->getAuth());
        $res = $bucketManager->fetch($url, $this->config['bucket'], $key);

        return isset($res[0]['key']) ? $res[0]['key'] : '';
    }
}