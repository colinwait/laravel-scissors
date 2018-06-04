<?php
return [
    /*
    |--------------------------------------------------------------------------
    | pocket host
    |--------------------------------------------------------------------------
    |
    */
    'host'             => env('POCKET_HOST'),

    /*
    |--------------------------------------------------------------------------
    | pocket host
    |--------------------------------------------------------------------------
    |
    */
    'mediaserver_host' => env('POCKET_MEDIA_HOST'),

    /*
    |--------------------------------------------------------------------------
    | pocket apis
    |--------------------------------------------------------------------------
    |
    */
    'apis'             => [
        'upload'                 => '/upload',
        'delete'                 => '/delete',
        'viewer'                 => '/viewer',
        'upload-material'        => '/material/upload',
        'upload-video'           => '/video/upload',
        'transcode-setting'      => '/transcode/setting',
        'transcode-status'       => '/transcode/status',
        'transcode-operation'    => '/transcode/operation',
        'transcode-status-lists' => '/transcode/status-lists',
        'video-split'            => '/video/split',
        'video-fast-edit'        => '/video/fast-edit',
        'video-screen-shot'      => '/video/screen-short',
        'retranscode'            => '/video/retranscode'
    ],

    /*
    |--------------------------------------------------------------------------
    | auth keys
    |--------------------------------------------------------------------------
    |
    */
    'access_key'       => env('POCKET_AK'),
    'secret_key'       => env('POCKET_SK'),

    /*
    |--------------------------------------------------------------------------
    | expire time
    |--------------------------------------------------------------------------
    |
    */
    'expire'           => '3600',

    /*
    |--------------------------------------------------------------------------
    | bucket space
    |--------------------------------------------------------------------------
    |
    */
    'bucket'           => env('POCKET_BUCKET'),

    /*
    |--------------------------------------------------------------------------
    | 图片访问地址
    |--------------------------------------------------------------------------
    |
    */
    'image_host'       => env('IMAGE_HOST'),

    /*
    |--------------------------------------------------------------------------
    | 音视频访问地址
    |--------------------------------------------------------------------------
    |
    */
    'media_host'       => env('MEDIA_HOST'),

    /*
    |--------------------------------------------------------------------------
    | 源文件访问地址
    |--------------------------------------------------------------------------
    |
    */
    'file_host'        => env('FILE_HOST'),

    /*
    |--------------------------------------------------------------------------
    | 上传路径
    |--------------------------------------------------------------------------
    |
    */
    'upload_dir'       => env('UPLOAD_DIR', '/storage/vod/uploads/'),
];