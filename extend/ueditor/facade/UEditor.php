<?php
// +----------------------------------------------------------------------
// | QingCMS [ MAKE THINGS BETTER ]
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <weibo.com/770878450>
// +----------------------------------------------------------------------
namespace ueditor\facade;
use think\Facade;

/**
 * 百度UEditor门面类
 * @see \ueditor\UEditor
 * @method action() 初始化方法
 */
class UEditor extends Facade
{
	
	protected static function getFacadeClass()
    {
    	return 'ueditor\UEditor';
    }
}
