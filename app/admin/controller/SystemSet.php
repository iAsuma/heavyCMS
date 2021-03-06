<?php
namespace app\admin\controller;
use auth\enum\AuthEnum;
use auth\facade\Permissions;
use think\facade\Db;
use think\facade\Cache;
use think\facade\View;
use think\Request;

/**
 * 系统相关设置
 */
class SystemSet extends Base
{
    protected $authUserTable;

    protected function initialize()
    {
        parent::initialize();
        $this->authUserTable = config('auth.auth_config.auth_user');
    }

	public function userInfo()
	{
		$uid = $this->request->uid;
		$cacheKey= md5('adminUser_'.$uid);
		$userInfo = $uid ? Db::name($this->authUserTable)->where('id', '=', $uid)->cache($cacheKey, 30*24*60*60, AuthEnum::CACHE_ADMIN_TAG)->find() : '';

		$roles = Permissions::getGroups($uid);
		$rolesArr = array_column($roles, 'title');
		$rolesStr = implode("，", $rolesArr);

		View::assign('user', $userInfo);
		View::assign('roles', $rolesStr);
		return View::fetch();
	}

	public function updateUserInfo(Request $request)
	{
		$uid = $request->uid;
		if(empty($uid)) return res_json(-2, '非法修改');

        if(checkFormToken($request->post())){
            $validate = new \app\admin\validate\Register;
            if(!$validate->scene('modify')->check($request->post())){
                return res_json(-1, $validate->getError());
            }

            try {
                $data = [
                    'name' => $request->post('truename'),
                    'phone' => $request->post('phone'),
                    'email' => $request->post('email'),
                    'remark' => $request->post('remark'),
                    'update_time' => time(),
                    'update_by' => $this->uid
                ];

                $where = $this->parseWhere([
                    ['email', '=', $data['email']],
                    ['phone', '=', $data['phone']]
                ]);

                $loginUser = Db::name($this->authUserTable)
                ->field('id,name,login_name,phone,email')
                ->where(function($query) use($where){
                    $query->whereOr($where);
                })
                ->where('id', '<>', $uid)
                ->where('status', '<>', -1)
                ->select();

                if(in_array($data['phone'], array_column($loginUser, 'phone'))) return res_json(-3, '手机号已注册');
                if(in_array($data['email'], array_column($loginUser, 'email'))) return res_json(-3, '邮箱已注册');

                $update = Db::name($this->authUserTable) ->where('id', $uid) -> update($data);
                if($update === false) return res_json(-6, '修改失败');

                Cache::tag(AuthEnum::CACHE_ADMIN_TAG)->clear(); //清除用户数据缓存
                
                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-5, '系统错误'.$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
	}

	public function password()
	{
		return View::fetch();
	}

	public function changePwd(Request $request)
	{
		$uid = $request->uid;
		if(empty($uid)) return res_json(-2, '非法修改');

        if(checkFormToken($request->post())){
            $validate = new \app\admin\validate\Register;
            if(!$validate->scene('changepwd')->check($request->post())){
                return res_json(-1, $validate->getError());
            }

            try {
                $data = [
                    'password' => md5safe($request->post('password')),
                    'update_time' => time(),
                    'update_by' => $this->uid
                ];

                $cacheKey= md5('adminUser_'.$uid);
                $userInfo = Db::name($this->authUserTable)->where('id', '=', $uid)->cache($cacheKey, 30*24*60*60, AuthEnum::CACHE_ADMIN_TAG)->find();

                if($userInfo['password'] != md5safe($request->post('oldPassword'))) return res_json(-6, '当前密码错误');

                $update = Db::name($this->authUserTable) ->where('id', $uid) -> update($data);
                if($update === false) return res_json(-6, '修改失败');

                Cache::tag(AuthEnum::CACHE_ADMIN_TAG)->clear(); //清除用户数据缓存
                
                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-5, '系统错误'.$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
	}

	public function notice()
	{
		return view('/public/error', ['icon' => '#xe6af', 'error' => '消息通知功能正在建设']);
	}
}