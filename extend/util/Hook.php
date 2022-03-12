<?php

namespace util;

use think\facade\Event;

/**
 * 兼容thinkphp5.1的钩子
 * */
class Hook
{

    /**
     * 该方法已弃用，仅为了兼容tp5.1的旧版本业务代码
     * @deprecated
     * */
    public static function listen(string $eventName,array $param)
    {
        Event::trigger($eventName, $param);
    }
}