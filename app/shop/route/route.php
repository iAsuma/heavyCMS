<?php
use think\facade\Route;

/*
 *
 * 商城路由未完全实现，若需要使用请自行实现，
 * 由于启用了强制路由，若没有配置路由将会报错
 *
 * */
Route::group('/', function(){
    Route::group('', function (){
        Route::get('/', 'shop/Index/index');
        Route::get('g/:id', 'shop/Goods/detail');
    });

})->middleware([
    app\common\middleware\AutoLogin::class
]);