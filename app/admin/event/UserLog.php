<?php


namespace app\admin\event;


class UserLog
{
    public function __construct(){
        echo "我是事件UserLog";
        i_log("我是事件UserLog");
    }
}