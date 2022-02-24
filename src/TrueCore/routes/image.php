<?php

Route::group([
    'namespace' => 'Image',
    'prefix'    => 'image',
], function () {
    Route::post('preview', [
        'as'   => 'preview',
        'uses' => 'ResizeController@store'
    ]);
});