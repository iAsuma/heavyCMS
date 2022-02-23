<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello/:name', 'index/hello');

Route::get('redirect', 'index/redirectLast'); //记住的跳转地址
Route::get('only-wechat', 'index/Login/onlyWechat');

Route::group('admin', function(){
    Route::get('/', 'admin/index/index');
    Route::rule('ueditor' , 'admin/index/ueditor');
    Route::rule('layuiUpload', 'admin/index/layuiUpload');
});

Route::group('shop', function(){
    Route::get('/', 'shop/Index/index');
    Route::get('g/:id', 'shop/Goods/detail');
});