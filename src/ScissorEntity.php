<?php


namespace Colinwait\LaravelScissors;

class ScissorEntity
{
    private $config;

    private $source;

    private $positions = [
        'top-left',
        'top',
        'top-right',
        'left',
        'center',
        'right',
        'bottom-left',
        'bottom',
        'bottom-right',
    ];

    private $position_num = [1, 2, 3, 4, 5, 6, 7, 8, 9];

    public function __construct()
    {
        $this->config = config('scissor');
    }

    public function generateToken($key = null, $bucket = null, $private = 0)
    {
        $once        = str_random(32);
        $timestamp   = time();
        $params      = [
            'once'      => $once,
            'timestamp' => $timestamp,
            'expire'    => $this->config['expire'],
            'private'   => $private ? 1 : 0,
            'bucket'    => $bucket ? $bucket : $this->config['bucket'],
            'key'       => $key,
        ];
        $policy      = safe_base64url_encode(json_encode($params));
        $encode_sign = safe_base64url_encode(hash_hmac('sha1', $policy, $this->config['secret_key'], true));
        $token       = $this->config['access_key'] . ':' . $encode_sign . ':' . $policy;

        return $token;
    }

    public function upload($source, $key = null, $private = 0, $bucket = null)
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
                $client->setMultiPartParams('data', safe_base64url_encode($source));
                break;

            case $this->isBase64():
                $client->setMultiPartParams('data', safe_base64url_encode(base64_decode($source)));
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
        $client->setMultiPartParams('token', $this->generateToken($key, $bucket, $private));

        return $client->request();
    }

    public function delete($key, $bucket = null)
    {
        $url    = $this->config['host'] . $this->config['apis']['delete'] . '/' . $key;
        $client = new Client('DELETE', $url);
        $client->setFormParams('token', $this->generateToken($key, $bucket));

        return $client->request();
    }

    private function isFileSource($source = null)
    {
        $this->source = $source ?: $this->source;
        return is_a($this->source, 'Symfony\Component\HttpFoundation\File\UploadedFile');
    }

    private function isDataUrl($source = null)
    {
        $this->source = $source ?: $this->source;
        $data         = $this->decodeDataUrl($this->source);

        return is_null($data) ? false : true;
    }

    private function isBase64($source = null)
    {
        $this->source = $source ?: $this->source;
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

    private function isUrl($source = null)
    {
        $this->source = $source ?: $this->source;
        return (bool)filter_var($this->source, FILTER_VALIDATE_URL);
    }

    private function isFilePath($source = null)
    {
        $this->source = $source ?: $this->source;
        if (is_string($this->source)) {
            try {
                return is_file($this->source);
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    public function getViewer($viewer)
    {
        $url    = $this->config['host'] . $this->config['apis']['viewer'] . '/' . $viewer;
        $client = new Client('GET', $url);
        return $client->request();
    }

    public function updateViewer($viewer, $data)
    {
        $url    = $this->config['host'] . $this->config['apis']['viewer'];
        $client = new Client('PUT', $url);
        if (isset($data['water_position']) && is_numeric($data['water_position'])) {
            if (!in_array($data['water_position'], $this->position_num)) {
                return ['error' => 'Position wrong'];
            }
            $data['water_position'] = $this->positions[$data['water_position'] - 1];
        }
        if (isset($data['file']) && $this->isFileSource($data['file'])) {
            $data['file'] = fopen($data['file']->getRealPath(), 'r');
        }
        foreach ($data as $key => $item) {
            $client->setMultiPartParams($key, $item);
        }
        $client->setMultiPartParams('viewer', $viewer);

        return $client->request();
    }
}