<?php
namespace app\admin\facade;
use think\Facade;
/**
 * 注册/登录门面
 * @see \app\admin\service\Entrance
 * @method isLogin() 判断用户登录状态
 * @method getAdminUserInfo(string $username) 返回管理员信息
 * @method check(array $input, array $loginUser, bool $boolVal = true) 验证登录/注册
 * @method login(array $user, $remembered=false) 注册用户的登录状态
 * @method getUserInfoFromCookie() 从cookie读取用户信息
 * @method logout() 注销登录
 */
class Entrance extends Facade
{
	
	protected static function getFacadeClass(): string
    {
    	return \app\admin\service\Entrance::class;
    }
}