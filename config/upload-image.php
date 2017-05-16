<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Default Upload Driver
    |--------------------------------------------------------------------------
    |
    | Two Driver available : qiniu、default
    |
    */

    'driver' => 'default',

    'qiniu' => [
        'accessKey' => '',
        'secretKey' => '',
        'bucket' => ''
    ],
];