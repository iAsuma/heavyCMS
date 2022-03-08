<?php
// +----------------------------------------------------------------------
// | App检测并且自动获取授权 中间件
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <sqiu_li@163.com>
// +----------------------------------------------------------------------

namespace app\middleware;
use app\common\Request;
use think\facade\Session;
use wechat\facade\Loader as WeChat;

class InAppCheck
{
    public function handle(Request $request, \Closure $next)
    {
    	if (preg_match('~micromessenger~i', $request->header('user-agent'))) {
            $request->InApp = 'WeChat';

            if(!Session::has('wapUser.wx_openid')){
                //微信授权
                $this->wechat($request); 
            }

        } else if (preg_match('~alipay~i', $request->header('user-agent'))) {
            $request->InApp = 'Alipay';
        } else if (preg_match('~dingtalk~i', $request->header('user-agent'))) {
            $request->InApp = 'DingTalk';
        } else{
        	$request->InApp = 'Normal';
        }
        
    	return $next($request);
    }

    public function wechat(Request $request)
    {
        if($request->get('code')){
            $app = WeChat::officialAccount();
            $user = $app->oauth->user();
            
            Session::set('wapUser.wx_openid', $user['id']);
            Session::set('wapUser.wx_user_info', $user['original']);
        }else{
            $config['oauth'] = [
                'scopes'   => ['snsapi_userinfo'], //snsapi_base  or snsapi_userinfo
                'callback' => $this->getTargetUrl($request),
            ];

            $app = WeChat::officialAccount($config);
            
            header("location: ". $app->oauth->redirect()->getTargetUrl());
            exit(); //执行跳转后进行业务隔离阻断，防止程序继续执行
        }
    }

    protected function getTargetUrl(Request $request): string
    {
        $param = $request->get();
        if (isset($param['code'])) {
            unset($param['code']);
        }
        if (isset($param['state'])) {
            unset($param['state']);
        }
        return $request->baseUrl() . (empty($param) ? '' : '?' . http_build_query($param));
    }
}