<?php

Route::group([
    'namespace' => 'Admin',
    'prefix'    => 'admin',
    'guard'     => 'api',
], function() {

    Route::post('login', [
        'as'   => 'login',
        'uses' => 'Common\AuthController@login',
    ]);
    Route::post('logout', [
        'as'         => 'logout',
        'middleware' => 'auth:api',
        'uses'       => 'Common\AuthController@logout',
    ]);

    Route::post('forgotten', [
        'as'   => 'forgotten',
        'uses' => 'Common\AuthController@forgotten',
    ]);

    Route::get('set_password/{token}', [
        'as'   => 'set_password',
        'uses' => 'Common\SetPasswordController@index',
    ]);

    Route::post('/set_password', [
        'as'   => 'set_password.save',
        'uses' => 'Common\SetPasswordController@save',
    ]);

    Route::get('me', [
        'as'         => 'me',
        'middleware' => 'auth:api',
        'uses'       => 'Common\AuthController@me',
    ]);

    Route::group([
        'as'         => 'admin.',
        'middleware' => ['auth:api', \TrueCore\App\Http\Middleware\ClearTempImage::class],
    ], function() {

        Route::post('/common/uploadTempImage',
            ['as' => 'common.upload_temp_image', 'uses' => 'Common\UploadTempImageController@uploadTempImage']);
        Route::post('/common/uploadTempDoc',
            ['as' => 'common.upload_temp_doc', 'uses' => 'Common\UploadTempDocController@uploadTempDoc']);

//        Route::get('autocomplete/{entity}',
//            ['as' => 'autocomplete', 'uses' => 'Common\AutocompleteController@index']);

        Route::group([
            'namespace' => 'System',
            'prefix'    => 'system',
            'as'        => 'system.',
        ], function() {

            Route::delete('cache', [
                'as'   => 'settings.cache',
                'uses' => 'SettingController@clearCache',
            ]);

            Route::resource('users', 'UserController', ['except' => ['create', 'edit']]);
            Route::post('users/{id}/switch/{field}', ['as' => 'users.switch', 'uses' => 'UserController@switch']);

            Route::resource('roles', 'RoleController', ['except' => ['create', 'edit']]);
            Route::get('entities', ['as' => 'entities', 'uses' => 'RoleController@entityList']);
        });

    });

});


