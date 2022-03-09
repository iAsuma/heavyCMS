<?php
// +----------------------------------------------------------------------
// | 后台权限检测中间件
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <sqiu_li@163.com>
// +----------------------------------------------------------------------
// | Time: 2019-05-06
// +----------------------------------------------------------------------
namespace app\admin\middleware;
use app\admin\facade\Entrance;
use think\facade\Session;
use auth\facade\Permissions;
use think\facade\Route;
use app\common\Request;

class BackAuthLogin
{
    /**
     * 跳转地址，支持符合规则的路由或模块/控制器/方法
     *
     */
    protected string $redirect_url = '/admin/login';  // 必设项，检测到未登录时的跳转地址

    /**
     * 排除的验证地址
     * 优先匹配路由规则，或者模块/控制器/方法。若需要精确到方法，请使用完整的模块路由地址（例：模块/控制器/方法）
     * 支持 模块，模块/控制器，模块/控制器/方法
     * @var array
     */
    protected array $except = [
        'admin/login',
    ];

    public function handle(Request $request, \Closure $next, $name="")
    {
        if($request->checkRouteInList($this->except)){
            return $next($request);
        }

    	if(!Entrance::isLogin()){
            //用户未登录后跳转
            if($request->isAjax()){
                //返回head头 ajax的url请求由js接收跳
                return response()->header([
                    'Ajax-Mark' => ' redirect',
                    'Redirect-Path' => (string)Route::buildUrl($this->redirect_url)
                ]);
            }else{
                return redirect($this->redirect_url);
            }
    	}else{
            $userInfo = Session::get(config('auth.auth_session_key'));
            $node = $request->controller().'/'.$request->action();

            // 权限检测
            if(!Permissions::check($node, $userInfo['uid'])){
                if($request->isAjax()){
                    return res_json(-101, '没有权限操作哦');
                }else{
                    return view('/public/error', ['icon' => '#xe6af', 'error' => '没有权限访问哦']);
                }
            }

            $request->uid = $userInfo['uid'];
            $request->uname = $userInfo['uname'];
            $request->ulogin = $userInfo['ulogin'];
        }

    	return $next($request);
    }
}
