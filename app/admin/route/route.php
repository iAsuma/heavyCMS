<?php
use think\facade\Route;

Route::group('/', function (){
    Route::get('/', 'admin/index/index');
    Route::get('login', 'admin/Login/index');
    Route::post('login/check', 'admin/Login/checkLogin');
    Route::rule('ueditor' , 'admin/index/ueditor');
    Route::rule('layuiUpload', 'admin/index/layuiUpload');
})->middleware([
    app\admin\middleware\BackAuthLogin::class
]);
