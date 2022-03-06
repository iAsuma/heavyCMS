<?php

namespace util;

use think\facade\Event;

/**
 * 兼容thinkphp5.1的钩子
 * */
class Hook
{

    /**
     * @deprecated
     * */
    public static function listen(string $eventName,array $param){
        Event::trigger($eventName, $param);
    }
}