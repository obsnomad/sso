<?php

Route::group([
    'as' => 'sso::',
    'prefix' => config('ssoserver.route_prefix'),
    'namespace' => 'ObsNomad\\SSO\\Http\\Controllers'
], function () {
    Route::any('/login', 'ServerController@login')->name('login');
    Route::any('/attach', 'ServerController@attach')->name('attach');
    Route::any('/user', 'ServerController@user')->name('user');
    Route::any('/logout', 'ServerController@logout')->name('logout');
});
