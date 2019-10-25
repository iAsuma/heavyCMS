<?php
// +----------------------------------------------------------------------
// | App检测并且自动获取授权 中间件
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <sqiu_li@163.com>
// +----------------------------------------------------------------------

namespace app\http\middleware;
use Session;
use think\Db;
use EasyWeChat\Factory as WeChat;

class InAppCheck
{
    public function handle($request, \Closure $next)
    {
    	if (preg_match('~micromessenger~i', $request->header('user-agent'))) {
            $request->InApp = 'WeChat';

            if(!Session::has('wapUser.wx_openid')){
                //读取微信配置并存入缓存
                $wxConfig = Db::table('application_config')->order('id', 'desc')->cache('wx_config', 0, 'developer')->find();
                //微信授权
                $this->wechat($request, $wxConfig); 
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

    public function wechat($request, $wxConfig)
    {
        $config = [
            'app_id' => $wxConfig['app_id'],
        ];
        
        if($request->get('code')){
            $config['secret'] = $wxConfig['app_secret'];
            $app = WeChat::officialAccount($config);
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

    protected function getTargetUrl($request)
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