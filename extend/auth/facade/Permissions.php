<?php
// +----------------------------------------------------------------------
// | QingCMS [ MAKE THINGS BETTER ]
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <weibo.com/770878450>
// +----------------------------------------------------------------------
namespace auth\facade;
use think\Facade;

/**
 * 权限类门面
 * @see \auth\Permissions
 * @method check($name, $uid, $relation = 'or', $mark = 1, $mode = 'url') static 监测管理系统权限
 * @method getMenu($uid, $mark=1) static 获取权限菜单列表
 */
class Permissions extends Facade
{

	protected static function getFacadeClass(): string
    {
    	return 'auth\Permissions';
    }
}
