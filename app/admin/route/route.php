<?php
use think\facade\Route;

Route::group('/', function (){
    Route::get('/', 'admin/index/index');

    Route::rule('ueditor' , 'admin/index/ueditor');
    Route::rule('layuiUpload', 'admin/index/layuiUpload');

    // 登录路由组
    Route::group('login' ,function (){
        Route::get('/', 'admin/Login/index'); //登录页
        Route::post('check', 'admin/Login/checkLogin'); //登录
        Route::rule('logout', 'admin/Login/logout'); //退出登录
    });

    // 面板路由组
    Route::group('panel', function (){
        Route::get('/', 'Panel/index'); // 面板首页
    });

    // 管理用户组
    Route::group('user', function (){
        Route::get('info', 'SystemSet/userInfo');
    });

})->middleware([
    app\admin\middleware\BackAuthLogin::class
]);
