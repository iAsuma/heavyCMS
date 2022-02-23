<?php

namespace app\admin\controller;

use think\Request;
use Db;
use think\facade\Hook;

class BasisSet extends Base
{
	/**
     * 轮播图
     * @author zhaoyun  
     */
    public function appset()
    {
        return $this->fetch();
    }

    public function dataList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
 
        $count = Db::table('application_config')->count();
        $data = Db::table('application_config')->page($page, $limit)->cache('appset', 0, 'developer')->order('id', 'desc')->select();
        return table_json($data, $count);
    }

     public function add()
    {
        return $this->fetch();
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

                $result = Db::table('application_config') -> insert($data);
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

    public function edit()
    {
        $id = (int)$this->request->get('id');
        $id && $info = Db::table('application_config')->where(['id' => $id])->find();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
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


            $result = Db::table('application_config')->where('id', (int)$post['id'])->update($data);
            !is_numeric($result) && exit(res_json_native(-1, '修改失败'));

            Hook::listen('admin_log', ['基础设置', '修改了应用配置']);
            \think\facade\Cache::clear('developer');  //清除配置缓存，让列表实时生效

            destroyFormToken($post);
            return res_json(1);
        } catch (\Exception $e) {
            return res_json(-100, $e->getMessage());
        }
        
    }

    public function del(Request $request)
    {
        $id = $request->post('id');
        if (Db::table('application_config') ->where('id', '=', $id) -> delete()) { 
            Hook::listen('admin_log', ['基础设置', '删除了应用配置']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
    }

}