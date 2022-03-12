<?php


namespace util;

/**
 * 兼容thinkphp5.1的验证器写法
 * */
class Validate
{
    /**
     * 该方法已弃用，仅为了兼容tp5.1的旧版本业务代码
     * @deprecated
     * */
    public static function make(array $rules = [], array $message = []) :\think\Validate
    {
        $validate = \think\facade\Validate::rule($rules);
        $validate->message($message);

        return $validate;
    }
}