<?php
use think\facade\Route;

Route::group('api', function(){
    Route::rule('wx/pay_notice', 'Api/wxPayNotice');
});