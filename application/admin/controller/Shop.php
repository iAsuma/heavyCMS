<?php

namespace app\admin\controller;

use think\Request;
use Db;
use think\facade\Hook;
/**
* 用户管理
* @author zhaoyun  
*/
class Shop extends Base
{

    /**
     * 用户列表
     * @author zhaoyun  
     */
    public function goods()
    {
        return $this->fetch();
    }

    public function dataList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
             ['shop_goods.status', '<>', '-1']
            ,['shop_goods.goods_name', 'LIKE', $get['goods_name'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);

        $countQuery = Db::table('shop_goods')->where($formWhere);
        $query = Db::table('shop_goods')->alias('g')->leftJoin('shop_classification c','c.id = g.classification_id')->field('g.id,g.goods_name,g.post_type,g.freight,c.name,g.status,g.is_sold,FROM_UNIXTIME(g.create_time, "%Y-%m-%d %h:%i:%s") AS create_time')->where($formWhere)->page($page, $limit)->order('g.id', 'desc');
        $count = $countQuery->count();
        $data = $query->select();
 
        return table_json($data, $count);
    }

    public function del(Request $request)
    {
        
        $id = $request->post('id');
        $data['status'] = -1 ;
    
        if (Db::table('shop_goods') ->where('id', '=', $id) -> update($data)) { 
            Hook::listen('admin_log', ['商品管理', '删除了商品']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
      
    }


     /**
     * 商品分类
     * @author zhaoyun  
     */
    public function classification()
    {
       
        return $this->fetch();
    }

    public function classList()
    {
        // 查询所有规则，用以排序子父级关系，并存入缓存(tag:auth_rule)
        $rules = Db::name('shop_classification')->field('id,name,pid')->select();       
        $tree = new \util\Tree($rules);
        $modsTree = $tree->table();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;

        $list = array_slice($modsTree, ($page-1)*$limit, $limit);
      
        return table_json($list, count($modsTree));
    }

    public function classAdd()
    {
        return $this->fetch();
    }

    public function addClass(Request $request)
    {
        if(checkFormToken($request->post())){
  
            try {
                $data = [
                    'name' => $request->post('name'),
                    'pid' => 0,
                ];

                $result = Db::table('shop_classification') -> insert($data);
                !$result && exit(res_json_native(-3, '添加失败'));
                Hook::listen('admin_log', ['商品分类', '添加了分类'.$data['name']]);

                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100,$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

    public function classEdit()
    {
        $id = (int)$this->request->get('id');
        $id && $info = Db::table('shop_classification')->where(['id' => $id])->find();
        isset($info) && $this->assign('info', $info);
        $pidname = Db::table('shop_classification')->field('name')->where(['id' => $info['pid']])->find();
        isset($pidname) && $this->assign('pidname', $pidname);
        return $this->fetch();
    }


    public function editClass(Request $request)
    {
        try {
            $post = $request->post();
            !checkFormToken($post) && exit(res_json_native('-2', '请勿重复提交'));

            $data = [
                    'name' => $request->post('name'),
            ];

            $result = Db::table('shop_classification')->where('id', (int)$post['id'])->update($data);
            !is_numeric($result) && exit(res_json_native(-1, '修改失败'));
            Hook::listen('admin_log', ['商品分类', '修改了类别'.$data['name']]);

            destroyFormToken($post);
            return res_json(1);
        } catch (\Exception $e) {
            return res_json(-100, $e->getMessage());
        }
        
    }

    //添加二级分类
    public function classSecond()
    {
        $pid = (int)$this->request->get('pid');
        $pid && $info = Db::table('shop_classification')->field('name,id')->where(['id' => $pid])->find();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }

    public function secondClass(Request $request)
    {
        if(checkFormToken($request->post())){
  
            try {
                $data = [
                    'name' => $request->post('name'),
                    'pid' => $request->post('id')
                ];

                $result = Db::table('shop_classification') -> insert($data);
                !$result && exit(res_json_native(-3, '添加失败'));
                Hook::listen('admin_log', ['商品分类', '添加了二级分类'.$data['name']]);

                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100,$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }


    public function classDel(Request $request)
    {
        $id = $request->post('id');
        $info = Db::table('shop_classification')->field('id')->where(['pid' => $id])->find();
        if($info){
           return json(['code' => -2, 'result' => '请先删除子类']);
        }
        if (Db::table('shop_classification') ->where('id','=', $id) -> delete()) {  
            Hook::listen('admin_log', ['商品分类', '删除了类别']);        
            return res_json(1); 
        } else {
            return json(['code' => -1, 'result' => '删除失败']);
        }

    }

    public function changeSold()
    {
        $id = (int)$this->request->post('id');

         switch ($this->request->post('is_sold')) {
            case 'true':
                $sold = 1;
                break;
            case 'false':
                $sold = 0;
                break;
            default:
                break;
        }

        $id && $res = Db::name('shop_goods')->where('id', '=', $id)->update(['is_sold' => $sold]);
        !$res && exit(json(['code' => -1, 'result' => '失败']));

        return json(['code' => 1, 'result' => '成功']);
    }


}