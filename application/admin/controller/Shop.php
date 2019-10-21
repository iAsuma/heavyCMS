<?php

namespace app\admin\controller;

use think\Request;
use Db;
use think\facade\Hook;
/**
* 微商城
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
        $info = Db::table('shop_classification')->field('id,name')->where('pid','>',0)->order('pid','asc')->select();
        isset($info) && $this->assign('info', $info);
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
            ,['shop_goods.classification_id', '=', $get['class_id'] ?? '']
            ,['shop_goods.is_sold', '=', $get['is_sold'] ?? '']
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
        $get = $this->request->get();

        $classifications = Db::name('shop_classification')->field('id,name,pid,main_img')->select();

        $class = new \util\Tree($classifications);
        $classTree = $class->table();

        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;

        $list = array_slice($classTree, ($page-1)*$limit, $limit);
        
        return table_json($list, count($classTree));
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
                'name' => $request->post('name')
            ];

            $post['pid'] && $data['main_img'] = app('upload')->base64ToThumbnailImage($request->post('image'), [100, 100]);

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
            $image = app('upload')->base64ToThumbnailImage($request->post('image'), [100, 100]);
      
            try {
                $data = [
                    'name' => $request->post('name'),
                    'main_img' => $image[1],
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
    // 商品上/下架
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


   /**
     * 轮播图
     * @author zhaoyun  
     */
    public function banner()
    {
        return $this->fetch();
    }

    public function bannerList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
            ['title', 'LIKE', $get['title'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);
        $countQuery = Db::table('shop_banner')->where($formWhere);
        $query = Db::table('shop_banner')->field('id,title,img,landing_url')->where($formWhere)->page($page, $limit)->order('id', 'desc');
        $count = $countQuery->count();
        $data = $query->select();
        return table_json($data, $count);
    }

     public function bannerAdd()
    {
        return $this->fetch();
    }

    public function addBanner(Request $request)
    {
        if(checkFormToken($request->post())){
    
            $image = app('upload')->base64ToThumbnailImage($request->post('image'), [600, 340]);
      
            try {
                $data = [
                    'title' => $request->post('title'),
                    'landing_url' => $request->post('landing_url'),
                    'img' => $image[1]
                ];

                $result = Db::table('shop_banner') -> insert($data);
                !$result && exit(res_json_native(-3, '添加失败'));
                Hook::listen('admin_log', ['首页管理', '添加了banner']);

                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100,$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

    public function bannerEdit()
    {
        $id = (int)$this->request->get('id');
        $id && $info = Db::table('shop_banner')->where(['id' => $id])->find();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }


    public function editBanner(Request $request)
    {
        try {
            $post = $request->post();
            !checkFormToken($post) && exit(res_json_native('-2', '请勿重复提交'));
            $image = app('upload')->base64ToThumbnailImage($request->post('image'), [100, 100]);
      
            $data = [
                    'title' => $request->post('title'),
                    'landing_url' => $request->post('landing_url'),
                    'img' => $image[1]
            ];

            $result = Db::table('shop_banner')->where('id', (int)$post['id'])->update($data);
            !is_numeric($result) && exit(res_json_native(-1, '修改失败'));
            Hook::listen('admin_log', ['首页管理', '修改了banner']);

            destroyFormToken($post);
            return res_json(1);
        } catch (\Exception $e) {
            return res_json(-100, $e->getMessage());
        }
        
    }

    public function bannerDel(Request $request)
    {
        
        $id = $request->post('id');
        if (Db::table('shop_banner') ->where('id', '=', $id) -> delete()) { 
            Hook::listen('admin_log', ['首页管理', '删除了轮播图']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
      
    }

    /**
     * 推荐位
     * @author zhaoyun  
     */
    public function recommended()
    {
        return $this->fetch();
    }

     public function recoList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;       

        $count = Db::table('shop_reco_place')->count();
        $data = Db::table('shop_reco_place')->page($page, $limit)->order('id', 'desc')->select();

        return table_json($data, $count);
    }

     public function recoAdd()
    {
        return $this->fetch();
    }

    public function addReco(Request $request)
    {
        if(checkFormToken($request->post())){
           try {
                $data = [
                    'title' => $request->post('title')
                ];

                $result = Db::table('shop_reco_place') -> insert($data);
                !$result && exit(res_json_native(-3, '添加失败'));
                Hook::listen('admin_log', ['首页管理', '添加了banner']);

                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100,$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

    public function recoEdit()
    {
        $id = (int)$this->request->get('id');
        $id && $info = Db::table('shop_reco_place')->where(['id' => $id])->find();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }


    public function editReco(Request $request)
    {
        try {
            $post = $request->post();
            !checkFormToken($post) && exit(res_json_native('-2', '请勿重复提交'));
            $data = [
                    'title' => $request->post('title')
            ];

            $result = Db::table('shop_reco_place')->where('id', (int)$post['id'])->update($data);
            !is_numeric($result) && exit(res_json_native(-1, '修改失败'));
            Hook::listen('admin_log', ['首页管理', '修改了banner']);

            destroyFormToken($post);
            return res_json(1);
        } catch (\Exception $e) {
            return res_json(-100, $e->getMessage());
        }
        
    }

    public function detail()
    {      
        $reco_id = (int)$this->request->get('id');
        $this->assign('reco_id', $reco_id);

        $reco = Db::table('shop_reco_goods')->field('goods_id')->where(['rec_id' => $reco_id])->select();
        $str = '';
        foreach ($reco as $k => $v) {
            $str .= $v['goods_id'].',';
        }
        $str = rtrim($str,',');
        $info = Db::table('shop_goods g')->field('g.id,g.goods_name,s.sku_img,s.price,s.market_price,s.goods_id')->leftJoin("(select min(price) price,market_price,goods_id,sku_img from shop_goods_sku GROUP BY goods_id) s",'s.goods_id=g.id')->where('g.id','in',$str)->select();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }


    public function detaildel(Request $request)
    {
        
        $gid = $request->post('gid');
        $rid = $request->post('rid');
        if (Db::table('shop_reco_goods') ->where(['goods_id'=>$gid,'rec_id'=>$rid]) -> delete()) { 
            Hook::listen('admin_log', ['首页管理', '删除了推荐位的商品']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
      
    }

     public function recogoods()
    {    
        $rid = (int)$this->request->get('rid');
        $this->assign('rid', $rid);
        $info = Db::table('shop_classification')->field('id,name')->where('pid','>',0)->order('pid','asc')->select();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }

    public function recogoodsList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
             ['shop_goods.status', '<>', '-1']
             ,['shop_goods.is_sold', '=', '1']
            ,['shop_goods.goods_name', 'LIKE', $get['goods_name'] ?? '']
            ,['shop_goods.classification_id', '=', $get['class_id'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);
        $countQuery = Db::table('shop_goods')->where($formWhere);

        $query = Db::table('shop_goods')->alias('g')->leftJoin('shop_classification c','c.id = g.classification_id')->field('g.id,g.goods_name,g.post_type,g.freight,c.name,g.status,g.is_sold,FROM_UNIXTIME(g.create_time, "%Y-%m-%d %h:%i:%s") AS create_time')->where($formWhere)->page($page, $limit)->order('g.id', 'desc');
        $count = $countQuery->count();
        $data = $query->select();
        return table_json($data, $count);
    }

    public function addrecogoods(Request $request)
    {
        
        $data['goods_id'] = $request->post('gid');
        $data['rec_id']  = $request->post('rid');
        if (Db::table('shop_reco_goods') ->insert($data)) { 
            Hook::listen('admin_log', ['首页管理', '添加了推荐位的商品']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
      
    }


}