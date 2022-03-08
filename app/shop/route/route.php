<?php
use think\facade\Route;

Route::group('/', function(){
    Route::group('', function (){
        Route::get('/', 'shop/Index/index');
        Route::get('g/:id', 'shop/Goods/detail');
    });

})->middleware([
    app\middleware\AutoLogin::class
]);