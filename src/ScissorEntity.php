<?php


namespace Colinwait\LaravelScissors;

class ScissorEntity
{
    private $config;

    private $source;

    public function __construct()
    {
        $this->config = config('scissor');
    }

    public function generateToken()
    {
        $once        = str_random(32);
        $timestamp   = time();
        $params      = ['once' => $once, 'timestamp' => $timestamp, 'expire' => $this->config['expire']];
        $policy      = safe_base64url_encode(json_encode($params));
        $encode_sign = safe_base64url_encode(hash_hmac('sha1', $policy, $this->config['secret_key'], true));
        $token       = $this->config['access_key'] . ':' . $encode_sign . ':' . $policy;

        return $token;
    }

    public function upload($source, $key = null, $private = 0)
    {
        $this->source = $source;
        $private      = intval($private) ? 1 : 0;
        $url          = $this->config['host'] . $this->config['apis']['upload'];
        $client       = new Client('POST', $url);
        switch (true) {
            case $this->isFileSource():
                $client->setMultiPartParams('file', fopen($source->getRealPath(), 'r'));
                break;

            case $this->isDataUrl():
                $client->setMultiPartParams('data_url', safe_base64url_encode($source));
                break;

            case $this->isBase64():
                $client->setMultiPartParams('data', $source);
                break;

            case $this->isUrl():
                $client->setMultiPartParams('url', safe_base64url_encode($source));
                break;

            case $this->isFilePath():
                $client->setMultiPartParams('file', fopen($source, 'r'));
                break;

            default:
                return ['error' => 'No data source'];
        }
        $client->setMultiPartParams('key', $key);
        $client->setMultiPartParams('private', $private);
        $client->setMultiPartParams('token', $this->generateToken());

        return $client->request();
    }

    private function isFileSource()
    {
        return is_a($this->source, 'Symfony\Component\HttpFoundation\File\UploadedFile');
    }

    private function isDataUrl()
    {
        $data = $this->decodeDataUrl($this->source);

        return is_null($data) ? false : true;
    }

    private function isBase64()
    {
        if (!is_string($this->source)) {
            return false;
        }

        return base64_encode(base64_decode($this->source)) === $this->source;
    }

    private function decodeDataUrl($data_url)
    {
        if (!is_string($data_url)) {
            return null;
        }

        $pattern = "/^data:(?:image\/[a-zA-Z\-\.]+)(?:charset=\".+\")?;base64,(?P<data>.+)$/";
        preg_match($pattern, $data_url, $matches);

        if (is_array($matches) && array_key_exists('data', $matches)) {
            return base64_decode($matches['data']);
        }

        return null;
    }

    private function isUrl()
    {
        return (bool)filter_var($this->source, FILTER_VALIDATE_URL);
    }

    private function isFilePath()
    {
        if (is_string($this->source)) {
            try {
                return is_file($this->source);
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
}