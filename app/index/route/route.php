<?php
use think\facade\Route;

Route::get('/', 'Index/index');
Route::get('redirect', 'Index/redirectLast'); //记住的跳转地址
Route::get('only-wechat', 'index/Login/onlyWechat');