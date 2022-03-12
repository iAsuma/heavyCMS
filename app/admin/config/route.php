<?php
return [
    //路由中间件
    'middleware' => [
        app\admin\middleware\BackAuthLogin::class,
        app\admin\middleware\LogAuto::class
    ]
];