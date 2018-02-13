<?php
return [
    /*
    |--------------------------------------------------------------------------
    | pocket host
    |--------------------------------------------------------------------------
    |
    */
    'host'       => env('POCKET_UPLOAD_HOST'),

    /*
    |--------------------------------------------------------------------------
    | pocket apis
    |--------------------------------------------------------------------------
    |
    */
    'apis'       => [
        'upload'                 => '/upload',
        'delete'                 => '/delete',
        'viewer'                 => '/viewer',
        'upload-material'        => '/material/upload',
        'upload-video'           => '/video/upload',
        'transcode-setting'      => '/transcode/setting',
        'transcode-status'       => '/transcode/status',
        'transcode-status-lists' => '/transcode/status-lists',
        'video-split'            => '/video/split',
        'video-fast-edit'        => '/video/fast-edit',
        'video-screen-shot'      => '/video/screen-short'
    ],

    /*
    |--------------------------------------------------------------------------
    | auth keys
    |--------------------------------------------------------------------------
    |
    */
    'access_key' => env('POCKET_AK'),
    'secret_key' => env('POCKET_SK'),

    /*
    |--------------------------------------------------------------------------
    | expire time
    |--------------------------------------------------------------------------
    |
    */
    'expire'     => '3600',

    /*
    |--------------------------------------------------------------------------
    | bucket space
    |--------------------------------------------------------------------------
    |
    */
    'bucket'     => env('POCKET_BUCKET'),
];