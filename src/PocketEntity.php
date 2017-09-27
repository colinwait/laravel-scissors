<?php


namespace Colinwait\LaravelPockets;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PocketEntity
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
        $this->config = config('pocket');
    }

    /**
     * 生成token
     *
     * @param array $request_params
     *
     * @return string
     */
    public function generateToken(array $request_params = [])
    {
        $once        = str_random(32);
        $timestamp   = time();
        $form_params = [
            'once'      => $once,
            'timestamp' => $timestamp,
            'expire'    => $this->config['expire'],
        ];
        $params      = array_merge($form_params, $request_params);
        $policy      = safe_base64url_encode(json_encode($params));
        $encode_sign = safe_base64url_encode(hash_hmac('sha1', $policy, $this->config['secret_key'], true));
        $token       = $this->config['access_key'] . ':' . $encode_sign . ':' . $policy;

        return $token;
    }

    /**
     * 上传图片
     *
     * @param      $source
     * @param null $key
     * @param int  $private
     * @param null $bucket
     *
     * @return array|mixed
     */
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
        $params = [
            'key'     => $key,
            'bucket'  => $bucket ?: $this->config['bucket'],
            'private' => $private ? 1 : 0,
        ];
        $client->setMultiPartParams('token', $this->generateToken($params));

        return $client->request();
    }

    /**
     * 删除图片
     *
     * @param      $key
     * @param null $bucket
     *
     * @return array|mixed
     */
    public function delete($key, $bucket = null)
    {
        $url    = $this->config['host'] . $this->config['apis']['delete'] . '/' . $key;
        $client = new Client('DELETE', $url);
        $params = [
            'key'    => $key,
            'bucket' => $bucket ?: $this->config['bucket'],
        ];
        $client->setFormParams('token', $this->generateToken($params));

        return $client->request();
    }

    /**
     * 是否是laravel文件资源
     *
     * @param null $source
     *
     * @return bool
     */
    private function isFileSource($source = null)
    {
        $this->source = $source ?: $this->source;
        return is_a($this->source, 'Symfony\Component\HttpFoundation\File\UploadedFile');
    }

    /**
     * 是否是图片资源
     *
     * @param null $source
     *
     * @return bool
     */
    private function isDataUrl($source = null)
    {
        $this->source = $source ?: $this->source;
        $data         = $this->decodeDataUrl($this->source);

        return is_null($data) ? false : true;
    }

    /**
     * 是否是base64格式
     *
     * @param null $source
     *
     * @return bool
     */
    private function isBase64($source = null)
    {
        $this->source = $source ?: $this->source;
        if (!is_string($this->source)) {
            return false;
        }

        return base64_encode(base64_decode($this->source)) === $this->source;
    }

    /**
     * 解码图片资源
     *
     * @param $data_url
     *
     * @return bool|null|string
     */
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

    /**
     * 是否是url
     *
     * @param null $source
     *
     * @return bool
     */
    private function isUrl($source = null)
    {
        $this->source = $source ?: $this->source;
        return (bool)filter_var($this->source, FILTER_VALIDATE_URL);
    }

    /**
     * 是否文件路径
     *
     * @param null $source
     *
     * @return bool
     */
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

    /**
     * 获取图片视图
     *
     * @param $viewer
     *
     * @return array|mixed
     */
    public function getViewer($viewer)
    {
        $url    = $this->config['host'] . $this->config['apis']['viewer'] . '/' . $viewer;
        $client = new Client('GET', $url);
        return $client->request();
    }

    /**
     * 更新图片视图
     *
     * @param $viewer
     * @param $data
     *
     * @return array|mixed
     */
    public function updateViewer($viewer, $data)
    {
        $url    = $this->config['host'] . $this->config['apis']['viewer'];
        $client = new Client('POST', $url);
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
        $param['bucket'] = $this->config['bucket'];
        $client->setMultiPartParams('token', $this->generateToken($param));

        return $client->request();
    }

    /**
     * 上传附件
     *
     * @param UploadedFile $source
     *
     * @return array|mixed
     */
    public function uploadMaterial(UploadedFile $source)
    {
        $url             = $this->config['host'] . $this->config['apis']['upload-material'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setMultiPartParams('file', fopen($source, 'r'), ['filename' => $source->getClientOriginalName()]);
        $client->setMultiPartParams('token', $this->generateToken($param));

        return $client->request();
    }

    /**
     * 上传视频音频
     *
     * @param UploadedFile $source
     *
     * @return array|mixed
     */
    public function uploadVideo($source, $callback_url = '')
    {
        $url             = $this->config['host'] . $this->config['apis']['upload-video'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        switch (true) {
            case $this->isFileSource($source):
                $client->setMultiPartParams('file', fopen($source->getRealPath(), 'r'), ['filename' => $source->getClientOriginalName()]);
                break;
            case $this->isFilePath($source):
                $client->setMultiPartParams('file', fopen($source, 'r'), ['filename' => basename($source)]);
                break;
            default:
                return ['error' => 'No data source'];
        }
        $client->setMultiPartParams('token', $this->generateToken($param));
        $client->setMultiPartParams('callback_url', $callback_url);

        return $client->request();
    }

    /**
     * 获取转码设置
     *
     * @return array|mixed
     */
    public function getTranscodeSettings()
    {
        $url             = $this->config['host'] . $this->config['apis']['transcode-setting'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));

        return $client->request();
    }

    /**
     * 更新设置
     *
     * @param $data
     *
     * @return array|mixed
     */
    public function updateTranscodeSettings($data)
    {
        $url             = $this->config['host'] . $this->config['apis']['transcode-setting'];
        $client          = new Client('PUT', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        foreach ($data as $key => $datum) {
            $client->setQuery($key, $datum);
        }

        return $client->request();
    }

    /**
     * 获取转码状态
     *
     * @param $hash_ids
     *
     * @return array|mixed
     */
    public function getTranscodeStatus($hash_ids)
    {
        $url             = $this->config['host'] . $this->config['apis']['transcode-status'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        $client->setQuery('hash_ids', $hash_ids);

        return $client->request();
    }

    /**
     * 快编
     *
     * @param        $data
     * @param string $callback_url
     *
     * @return array|mixed
     */
    public function videoFastEdit($data, $callback_url = '', $fast_edit_hash_id = '')
    {
        $url             = $this->config['host'] . $this->config['apis']['video-fast-edit'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));
        $client->setFormParams('data', $data);
        $client->setFormParams('callback_url', $callback_url);
        $client->setFormParams('fast_edit_hash_id', $fast_edit_hash_id);

        return $client->request();
    }

    /**
     * 拆条
     *
     * @param        $hash_id
     * @param        $start
     * @param        $duration
     * @param string $callback_url
     *
     * @return array|mixed
     */
    public function videoSplit($hash_id, $start, $duration, $callback_url = '', $split_hash_id = '')
    {
        $url             = $this->config['host'] . $this->config['apis']['video-split'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));
        $client->setFormParams('hash_id', $hash_id);
        $client->setFormParams('start', $start);
        $client->setFormParams('duration', $duration);
        $client->setFormParams('callback_url', $callback_url);
        $client->setFormParams('split_hash_id', $split_hash_id);

        return $client->request();
    }
}