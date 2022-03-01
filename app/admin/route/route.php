<?php
use think\facade\Route;

Route::group('/', function (){
    Route::get('/', 'admin/index/index');
    Route::get('login', 'admin/Login/index');
    Route::rule('ueditor' , 'admin/index/ueditor');
    Route::rule('layuiUpload', 'admin/index/layuiUpload');
});
