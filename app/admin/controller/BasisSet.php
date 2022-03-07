<?php

namespace app\admin\controller;

use think\facade\Cache;
use think\facade\View;
use think\Request;
use think\facade\Db;
use think\facade\Hook;

class BasisSet extends Base
{
	/**
     * 应用配置
     * @author zhaoyun  
     */
    public function appset()
    {
        return View::fetch();
    }

    public function appList()
    {
        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
 
        $count = Db::name('application_config')->count();
        $data = Db::name('application_config')->page($page, $limit)->cache('appset', 0, 'developer')->order('id', 'desc')->select();
        return table_json($data, $count);
    }

     public function appAddIndex()
    {
        return View::fetch();
    }

    public function addApp(Request $request)
    {
        if(checkFormToken($request->post())){
    
            try {
                $data = [
                    'app_name' => $request->post('app_name'),
                    'app_id' => $request->post('app_id'),
                    'app_secret' => $request->post('app_secret'),
                    'app_token' => $request->post('app_token'),
                    'mch_id' => $request->post('mch_id'),
                    'partnerkey' => $request->post('partnerkey'),
                    'type' => $request->post('type')
                ];

                $result = Db::name('application_config') -> insert($data);
                !$result && exit(res_json_native(-3, '添加失败'));
                Hook::listen('admin_log', ['基础设置', '添加了应用配置']);

                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100,$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

    public function editAddIndex()
    {
        $id = (int)$this->request->get('id');
        $id && $info = Db::name('application_config')->where(['id' => $id])->find();
        isset($info) && View::assign('info', $info);
        return View::fetch();
    }

    public function editApp(Request $request)
    {
        try {
            $post = $request->post();
            !checkFormToken($post) && exit(res_json_native('-2', '请勿重复提交'));
              $data = [
                    'app_name' => $request->post('app_name'),
                    'app_id' => $request->post('app_id'),
                    'app_secret' => $request->post('app_secret'),
                    'app_token' => $request->post('app_token'),
                    'mch_id' => $request->post('mch_id'),
                    'partnerkey' => $request->post('partnerkey'),
                    'type' => $request->post('type')
                ];


            $result = Db::name('application_config')->where('id', (int)$post['id'])->update($data);
            !is_numeric($result) && exit(res_json_native(-1, '修改失败'));

            Hook::listen('admin_log', ['基础设置', '修改了应用配置']);
            Cache::tag('developer')->clear();  //清除配置缓存，让列表实时生效

            destroyFormToken($post);
            return res_json(1);
        } catch (\Exception $e) {
            return res_json(-100, $e->getMessage());
        }
        
    }

    public function delApp(Request $request)
    {
        $id = $request->post('id');
        if (Db::name('application_config') ->where('id', '=', $id) -> delete()) { 
            Hook::listen('admin_log', ['基础设置', '删除了应用配置']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
    }

}