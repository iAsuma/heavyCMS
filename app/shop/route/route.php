<?php
use think\facade\Route;

Route::group('shop', function(){
    Route::get('/', 'shop/Index/index');
    Route::get('g/:id', 'shop/Goods/detail');
});