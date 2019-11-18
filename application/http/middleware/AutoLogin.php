<?php
// +----------------------------------------------------------------------
// | 自动注册登录中间件
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <sqiu_li@163.com>
// +----------------------------------------------------------------------

namespace app\http\middleware;
use Session;
use Url;
use think\Db;

class AutoLogin
{
    /**
     * 跳转地址，支持符合规则的路由或模块/控制器/方法
     *
     */
    protected $redirect_url = '/only-wechat';  // 必设项，检测到未登录时的跳转地址

    /**
     * 排除的验证地址
     * 支持完整的路由规则，或者模块/控制器/方法。若需要精确到方法，请使用完整的路由地址（模块/控制器/方法）
     *
     * @var array
     */
    protected $except = [
        'shop/Index',
        'shop/Goods',
        'shop/Api'
    ];

    public function handle($request, \Closure $next, $name)
    {
        if($this->inExceptArray($request)){
            return $next($request);
        }

    	$userId = Session::get('wapUser.id');

    	if(!$userId){
    		$user = [];

            //登录业务逻辑
            if($request->InApp == 'WeChat'){
                if(Session::get('wapUser.wx_openid') == ''){
                    return $next($request);
                }
                
                $where = ['wx_openid' => Session::get('wapUser.wx_openid')];
                $user = Db::table('users')->where($where)->where('status', '=', 1)->find();

                if(!$user){
                    //微信自动注册
                    $data = [
                        'nickname' => Session::get('wapUser.wx_user_info')['nickname'],
                        'create_time' => date('Y-m-d H:i:s'),
                        'gender' => (int)Session::get('wapUser.wx_user_info')['sex'],
                        'country' => Session::get('wapUser.wx_user_info')['country'],
                        'province' => Session::get('wapUser.wx_user_info')['province'],
                        'city' => Session::get('wapUser.wx_user_info')['city'],
                        'status' => 1,
                        'headimgurl' => Session::get('wapUser.wx_user_info')['headimgurl'],
                        'wx_openid' => Session::get('wapUser.wx_openid')
                    ];

                    $userid = Db::table('users')->insertGetId($data);

                    $user = [
                        'id' => $userid,
                        'nickname' => $data['nickname'],
                        'headimgurl' => $data['headimgurl']
                    ];
                }else{
                    //定期修改头像,防止微信头像失效
                    $head_img = Session::get('wapUser.wx_user_info')['headimgurl'];
                    Db::name('users')->where('status', '=' , '1')->where($where)->update(['headimgurl' => $head_img]);
                }
            }else{
                if($request->isAjax() || $request->isPost()){
                    header('Ajax-Mark: redirect');
                    header("Redirect-Path: ".Url::build($this->redirect_url));
                }else{
                    return redirect($this->redirect_url)->remember();
                }
                exit(); //执行跳转后进行业务隔离阻断，防止程序继续执行
            }
            
            Session::set('wapUser.id', $user['id']);
            Session::set('wapUser.name', $user['nickname']);
            Session::set('wapUser.headimgurl', $user['headimgurl']);
    	}else{
            if(Session::has('from_redirect')){
                Session::delete('from_redirect');
            }
        }

    	return $next($request);
    }

    protected function inExceptArray($request)
    {
        foreach ($this->except as $v) {
            if(strtolower($v) == $request->path() || $this->checkRoute($request, $v)){
                return true;
            }
        }

        return false;
    }

    protected function checkRoute($request, $pattern)
    {
        $patternArr = explode('/', $pattern);
        if(count($patternArr) == 3){
            if(strtolower($patternArr[0]) == strtolower($request->module()) && strtolower($patternArr[1]) == strtolower($request->controller()) && strtolower($patternArr[2]) == strtolower($request->action())){
                return true;
            }
        }else if(count($patternArr) == 2){
            if(strtolower($patternArr[0]) == strtolower($request->module()) && strtolower($patternArr[1]) == strtolower($request->controller())){
                return true;
            }
        }else{
            if(strtolower($patternArr[0]) == strtolower($request->module())){
                return true;
            }
        }

        return false;
    }
}
