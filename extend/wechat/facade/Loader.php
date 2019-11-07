<?php
// +----------------------------------------------------------------------
// | QingCMS [ MAKE THINGS BETTER ]
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <weibo.com/770878450>
// +----------------------------------------------------------------------
namespace wechat\facade;
use think\Facade;

/**
 * 权限类门面
 */
class Loader extends Facade
{
	
	protected static function getFacadeClass()
    {
    	return 'wechat\Loader';
    }
}
