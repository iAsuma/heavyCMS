<?php
// +----------------------------------------------------------------------
// | 权限行为自动记录日志中间件
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <sqiu_li@163.com>
// +----------------------------------------------------------------------
// | Time: 2019-06-21
// +----------------------------------------------------------------------
namespace app\admin\middleware;
use auth\enum\AuthEnum;
use util\Hook;
use think\facade\Db;

class LogAuto
{
    public function handle($request, \Closure $next)
    {
    	$response = $next($request);

    	$authname = $request->controller().'/'.$request->action();
    	$auth = Db::name('auth_rule')->field('id,name,title,is_logged,remark')->cache(md5($authname), 30*24*60*60, AuthEnum::CACHE_RULE_TAG)->where('name', $authname)->find();

    	if($auth && $auth['is_logged'] == 1){
    		Hook::listen('admin_log', [$auth['title'], $auth['remark']]); 
    	}
    	
    	return $response;
    }
}
