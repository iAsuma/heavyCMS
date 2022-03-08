<?php
namespace app\admin\facade;
use think\Facade;
/**
 * 注册/登录门面
 * @see \app\admin\service\Register
 * @method isLogined() 判断用户登录状态
 * @method check(array $post, bool $boolval = false, &$loginUser = []) 验证登录/注册
 * @method login(array $user, $remembered=false) 注册用户的登录状态
 * @method getUserInfoFromCookie() 从cookie读取用户信息
 * @method logout() 注销登录
 */
class Register extends Facade
{
	
	protected static function getFacadeClass(): string
    {
    	return 'register';
    }
}