<?php

return [
    'basicAuthPwd' => env('IMAGE_RESIZE_HTTP_PWD'),
    'driver'       => env('IMAGE_RESIZE_DRIVER', 'local'),

    'drivers'      => [
        'local' => [
            // We'll probably need some configuration here. In the future.
        ],
        'trueResizer'  => [
            'url'       => env('IMAGE_RESIZE_API_URL'),
            'authorization' => env('IMAGE_RESIZE_API_AUTHORIZATION'),
            'bucket'    => [
                'source'    => env('AWS_BUCKET'),
                'dest'      => env('AWS_BUCKET')
            ],
            'callback'  => [
                'url'           => env('APP_URL') . '/api/image/preview',
                'authorization' => 'Basic ' . env('IMAGE_RESIZE_HTTP_PWD')
            ]
        ]
    ],

    'sizeList' => []
];
