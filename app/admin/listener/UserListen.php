<?php


namespace app\admin\listener;


class UserListen
{
    public function handle()
    {
        echo "我是监听UserListen";
        i_log("我是监听UserListen");
    }
}