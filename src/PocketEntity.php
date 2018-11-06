<?php


namespace Colinwait\LaravelPockets;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PocketEntity
{
    private $config;

    private $source;

    private $site_id;

    private $site_config;

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
        $this->config  = config('pocket');
        $this->site_id = auth()->check() ? auth()->user()->site_id : 0;
        $this->init();
    }

    private function init()
    {
        if (!$this->site_id) {
            return false;
        }
        $this->site_config = $site_config = site_material_config();
        if ($host = array_get($site_config, "upload_config.{$this->site_id}.image_upload_host")) {
            $this->config['host'] = $host;
        }
        if ($media_host = array_get($site_config, "upload_config.{$this->site_id}.media_upload_host")) {
            $this->config['mediaserver_host'] = $media_host;
        }
        if ($ak = array_get($site_config, "upload_config.{$this->site_id}.access_key")) {
            $this->config['access_key'] = $ak;
        }
        if ($sk = array_get($site_config, "upload_config.{$this->site_id}.secret_key")) {
            $this->config['secret_key'] = $sk;
        }
        if ($bucket = array_get($site_config, "upload_config.{$this->site_id}.bucket")) {
            $this->config['bucket'] = $bucket;
        }
    }

    /**
     * 生成token
     *
     * @param array $request_params
     *
     * @return string
     */
    public function generateToken(array $request_params = [], $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $once         = str_random(32);
        $timestamp    = time();
        $form_params  = [
            'once'      => $once,
            'timestamp' => $timestamp,
            'expire'    => $this->config['expire'],
        ];
        $params       = array_merge($form_params, $request_params);
        $policy       = safe_base64url_encode(json_encode($params));
        $encode_sign  = safe_base64url_encode(hash_hmac('sha1', $policy, $this->config['secret_key'], true));
        $token        = $this->config['access_key'] . ':' . $encode_sign . ':' . $policy;

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
    public function upload($source, $key = null, $private = 0, $bucket = null, array $viewer = [])
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

        foreach ($viewer as $k => $v) {
            $client->setMultiPartParams($k, $v);
        }

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
     * @param $source
     *
     * @return array|mixed
     */
    public function uploadVideo($source, $callback_url = '', $setting_id = 0, $is_transcode = 1)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['upload-video'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        switch (true) {
            case $this->isFileSource($source):
                $client->setMultiPartParams('file', fopen($source->getRealPath(), 'r'), ['filename' => $source->getClientOriginalName()]);
                break;
            case $this->isFilePath($source):
                $client->setMultiPartParams('file', fopen($source, 'r'), ['filename' => basename($source)]);
                break;
            case $this->isUrl($source):
                $client->setMultiPartParams('url', safe_base64url_encode($source));
                break;
            case is_string($source):
                $client->setMultiPartParams('path', $source);
                break;
            default:
                return ['error' => 'No data source'];
        }
        $client->setMultiPartParams('token', $this->generateToken($param));
        $client->setMultiPartParams('callback_url', $callback_url);
        $client->setMultiPartParams('is_transcode', $is_transcode);
        $client->setMultiPartParams('setting_id', $setting_id);

        return $client->request();
    }

    /**
     * 上传多码流视频音频
     *
     * @param $source
     *
     * @return array|mixed
     */
    public function uploadMultiStreamVideoFromFtp($sources, $callback_url = '', $is_transcode = 1)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['upload-ftp-multi-video'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('paths', $sources);
        $client->setFormParams('token', $this->generateToken($param));
        $client->setFormParams('callback_url', $callback_url);
        $client->setFormParams('is_transcode', $is_transcode);

        return $client->request();
    }

    /**
     * 获取转码设置
     *
     * @return array|mixed
     */
    public function getTranscodeSettings($id = '')
    {
        $url = $this->config['mediaserver_host'] . $this->config['apis']['transcode-setting'];
        if ($id) {
            $url = $url . '/' . $id;
        }
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
    public function updateTranscodeSettings($id, $data)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['transcode-setting'] . '/' . $id;
        $client          = new Client('PUT', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        foreach ($data as $key => $datum) {
            $client->setQuery($key, $datum);
        }

        return $client->request();
    }

    /**
     * 创建转码设置
     *
     * @param $data
     *
     * @return array|mixed
     */
    public function createTranscodeSettings($data)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['transcode-setting'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));
        foreach ($data as $key => $datum) {
            $client->setFormParams($key, $datum);
        }

        return $client->request();
    }

    /**
     * 删除转码设置
     *
     * @param $data
     *
     * @return array|mixed
     */
    public function deleteTranscodeSettings($id)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['transcode-setting'] . '/' . $id;
        $client          = new Client('DELETE', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));

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
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['transcode-status'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        $client->setQuery('hash_ids', $hash_ids);

        return $client->request();
    }

    /**
     * 获取转码列表状态
     *
     * @param $hash_ids
     *
     * @return array|mixed
     */
    public function getTranscodeStatusLists()
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['transcode-status-lists'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));

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
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['video-fast-edit'];
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
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['video-split'];
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

    /**
     * 获取视频截图
     *
     * @param      $hash_id
     * @param null $num
     *
     * @return array|mixed
     */
    public function getVideoScreenShots($hash_id, $num = null)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['video-screen-shot'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        $client->setQuery('hash_id', $hash_id);
        $client->setQuery('num', $num);

        return $client->request();
    }

    /**
     * 转码操作，stop-停止
     *
     * @param $hash_ids
     * @param $operation
     *
     * @return array|mixed
     */
    public function transcodeOperation($hash_ids, $operation)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['transcode-operation'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));
        $client->setFormParams('hash_ids', $hash_ids);
        $client->setFormParams('operation', $operation);

        return $client->request();
    }

    /**
     * 重新转码
     *
     * @param $hash_ids
     * @param $callback_url
     *
     * @return array|mixed
     */
    public function videoRetranscode($hash_ids, $callback_url = null, $extend = null)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['retranscode'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));
        $client->setFormParams('hash_ids', $hash_ids);
        $client->setFormParams('callback_url', $callback_url);
        $client->setFormParams('extend', $extend);

        return $client->request();
    }

    /**
     * 视频字幕模板合成
     *
     * @param $hash_id
     * @param $captions
     * @param $texts
     * @param $callback_url
     *
     * @return array|mixed
     */
    public function videoSynthesis($hash_id, $captions, $texts, $callback_url)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['video-synthesis'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));
        $client->setFormParams('hash_id', $hash_id);
        $client->setFormParams('callback_url', $callback_url);
        $client->setFormParams('captions', $captions);
        $client->setFormParams('texts', $texts);

        return $client->request();
    }

    /*
    * 视频字幕模板合成
    *
    * @param $hash_id
    * @param $captions
    * @param $texts
    * @param $callback_url
    *
    * @return array|mixed
    */
    public function videoReSynthesis($from_hash_id, $hash_id, $captions, $texts, $callback_url)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['video-resynthesis'];
        $client          = new Client('POST', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setFormParams('token', $this->generateToken($param));
        $client->setFormParams('hash_id', $hash_id);
        $client->setFormParams('from_hash_id', $from_hash_id);
        $client->setFormParams('callback_url', $callback_url);
        $client->setFormParams('captions', $captions);
        $client->setFormParams('texts', $texts);

        return $client->request();
    }

    /**
     * 获取视频合成状态
     *
     * @param $hash_ids
     *
     * @return array|mixed
     */
    public function getSynthesisStatus($hash_ids)
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['synthesis-status'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        $client->setQuery('hash_ids', $hash_ids);

        return $client->request();
    }

    /**
     * 获取 ftp 目录文件
     *
     * @param $hash_ids
     *
     * @return array|mixed
     */
    public function getFtpFiles($path = '')
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['ftp'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        $client->setQuery('path', $path);

        return $client->request();
    }

    /**
     * 获取 ftp 目录结构
     *
     * @param $hash_ids
     *
     * @return array|mixed
     */
    public function getFtpPath($path = '')
    {
        $url             = $this->config['mediaserver_host'] . $this->config['apis']['ftp-path'];
        $client          = new Client('GET', $url);
        $param['bucket'] = $this->config['bucket'];
        $client->setQuery('token', $this->generateToken($param));
        $client->setQuery('path', $path);

        return $client->request();
    }
}