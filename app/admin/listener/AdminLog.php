<?php


namespace app\admin\listener;

use app\common\Request;
use think\Exception;
use think\facade\Db;
use think\facade\Log as ThinkLog;

class AdminLog
{
    public function handle(Request $request, $param)
    {
        try {
            $data['auth_name'] = $request->controller().'/'.$request->action();
            $data['auth_title'] = $param[0] ?? "";
            $data['auth_desc'] = $param[1] ?? "";
            $data['ip'] = $request->ip();
            $data['record_time'] = date('Y-m-d H:i:s');

            $userInfo = session(config('auth.auth_session_key'));
            $data['behavior_user'] = $userInfo['ulogin'];

            $result = Db::name('operation_log')->insert($data);
            if(!$result){
                throw new Exception("未记录到行为操作日志");
            }

        } catch (Exception $e) {
            ThinkLog::record('权限行为日志记录异常，异常信息：'.$e->getMessage(), 'error');
        }
    }
}