<?php
namespace app\admin\service;
use app\admin\model\AdminUser;
use think\facade\Session;
use think\facade\Config;
use think\facade\Cookie;

// +----------------------------------------------------------------------
// | 注册/登录类库
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <sqiu_li@163.com>
// +----------------------------------------------------------------------
// | Time: 2019-05-08
// +----------------------------------------------------------------------
class Entrance
{
	protected $key = '';
	protected $cookie_key = '';

	public function __construct()
	{
		$this->key = Config::get('auth.auth_session_key');
		$this->cookie_key = Config::get('auth.auth_cookie_key');
	}

    /**
	*  判断用户登录状态
	*/
	public function isLogin()
	{
		if(Session::has($this->key.'.uid')){
			return true;
		}else{
			$user = $this->getUserInfoFromCookie();
			return $user ? $this->login($user) : false;
		}
	}

    /**
     *  验证登录
     *  @param array $input 表单提交信息
     *  @param array $loginUser 用户信息
     *  @param bool $boolval 为true返回值布尔类型，否则返回用户信息
     *  成功返回用户信息
     */
    public function check(array $input, array $loginUser, bool $boolVal = true)
    {
        if(empty($input['username'])) return '账号为空';
        if(empty($loginUser)) return '账号不存在';

        if(empty($loginUser['password']) || md5safe($input['password']) != $loginUser['password']){
            return '用户名或密码错误';
        }

        if($loginUser['status'] == -2) return '账号已被冻结';
        if($loginUser['status'] == 0) return '账号正在审核';

        return $boolVal ? true : $loginUser;
    }

    /**
	 * 获取管理员用户信息
	 * */
	public function getAdminUserInfo($userName): array
    {
        $adminUserModel = new AdminUser();
        $loginUser = $adminUserModel->getInfo($userName);

        return $loginUser->toArray();
    }

	/**
	*  注册用户的登录状态
	*  @param array $user 用户信息
	*  @param bool $remembered 是否记住登录状态
	*/
	public function login(array $user, $remembered=false): bool
	{
		if(empty($user)){
			return false;
		}

		$key = $this->key;
		Session::set("$key.uid", $user['id']);
		Session::set("$key.uname", $user['name']);
		Session::set("$key.ulogin", $user['login_name']);

		//记住登录状态
		if($remembered){
			$expire = 24*60*60; //登录状态最长有效时间为24小时
			$pwd = str_rand(4).$user['password'].str_rand(4);
			$cookie_value = i_base64encode($user['id'].'_'.$user['login_name'].'_'.$pwd);
			Cookie::set($this->cookie_key, $cookie_value, $expire);
		}

		return true;
	}

	/**
	*  从cookie读取用户信息
	*/
	public function getUserInfoFromCookie(): array
	{
		if(Cookie::has($this->cookie_key)){
			$userCookie = i_base64decode(Cookie::get($this->cookie_key));
			$secret = explode('_', $userCookie);
			
			if(count($secret) != 3){
				return [];
			}

			static $cookieUser = [];
			if(!isset($cookieUser['id'])){
				$pwd = substr($secret[2], 4, -4);
                $adminUserModel = new AdminUser();
                $cookieUser = $adminUserModel->getForceInfo($secret[0], $secret[1], $pwd)->toArray();
			}

			return $cookieUser ?: [];
		}

		return [];
	}

	/**
	*  注销登录
	*/
	public function logout()
	{
		//清除Session
		Session::delete($this->key);
		//清除Cookie
		Cookie::delete($this->cookie_key);
	}
}