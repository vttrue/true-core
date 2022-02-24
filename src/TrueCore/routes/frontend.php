<?php

Route::group([
    'namespace' => 'Api\Pub',
    'prefix'    => 'public',
], function () {

    Route::group([
        'namespace' => 'System',
    ], function () {

        Route::get('/settings', [
            'as'   => 'settings.all',
            'uses' => 'SettingController@getItemList',
        ]);

        Route::get('/settings/time', [
            'as'   => 'settings.getTime',
            'uses' => 'SettingController@getTime',
        ]);

        Route::get('/settings/{group}/{key?}', [
            'as'   => 'settings.get',
            'uses' => 'SettingController@getItem',
        ]);
    });
});
