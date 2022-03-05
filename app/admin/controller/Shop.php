<?php

namespace app\admin\controller;

use think\facade\View;
use think\Request;
use think\facade\Db;
use think\facade\Hook;
/**
* 微商城
*/
class Shop extends Base
{
    public function goods()
    {
        $classifyArr = Db::name('shop_classification')->field('id,name,pid')->select();

        $tree = new \util\Tree($classifyArr);
        $classify = $tree->leaf();
        
        View::assign('classify', $classify);
        return View::fetch();
    }

    public function goodsList()
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
        $count = Db::name('shop_goods')->where($formWhere)->count();

        $data = Db::name('shop_goods')->alias('g')->field('g.id,g.goods_name,g.goods_imgs,g.post_type,g.freight,g.status,g.is_sold,FROM_UNIXTIME(g.create_time, "%Y-%m-%d %H:%i:%s") AS create_time,c.name cname,cc.name ccname')->leftJoin('shop_classification c','c.id = g.classification_id')->leftJoin('shop_classification cc', 'c.pid=cc.id')->where($formWhere)->page($page, $limit)->order('g.id', 'desc')->select();

        foreach ($data as $k => $val) {
            $arr = explode(',',$val['goods_imgs']);
            $data[$k]['imgs'] = $arr[0];
        }
     
        return table_json($data, $count);
    }

    public function del(Request $request)
    {
        $id = $request->post('id');
        $del = Db::name('shop_goods')->where('id', '=', $id)->update(['status' => -1]);

        Db::name('shop_goods_sku')->where('goods_id', '=', $id)->update(['status' => -1]);

        if ($del) { 
            Hook::listen('admin_log', ['商品管理', '删除了商品']);
            return res_json(1); 
        } else {
            return res_json(-1, '系统错误');
        }
    }

     /**
     * 商品分类
     * @author zhaoyun  
     */
    public function classification()
    {
        return View::fetch();
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
        return View::fetch();
    }

    public function addClass(Request $request)
    {
        if(checkFormToken($request->post())){
            $validate = \think\Validate::make([
                'name' => 'require|min:2',
            ],[
                'name.require'=> '请填写分类名称',
                'name.min'    => '分类名称最少不能少于2个字符'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

            try {
                $data = [
                    'name' => $request->post('name'),
                    'pid' => 0,
                ];

                $result = Db::name('shop_classification') -> insert($data);
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
        $id && $info = Db::name('shop_classification')->where(['id' => $id])->find();
        isset($info) && View::assign('info', $info);
        $pidname = Db::name('shop_classification')->field('name')->where(['id' => $info['pid']])->find();
        isset($pidname) && View::assign('pidname', $pidname);
        return View::fetch();
    }


    public function editClass(Request $request)
    {   
        if(checkFormToken($request->post())){
            $validate = \think\Validate::make([
                'name' => 'require|min:2',
            ],[
                'name.require'=> '请填写分类名称',
                'name.min'    => '分类名称最少不能少于2个字符'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

            try {
                $post = $request->post();

                $data = [
                    'name' => $request->post('name')
                ];
                if($post['pid'] > 0 && $request->post('image')){
                    $img = app('upload')->base64ToThumbnailImage($request->post('image'), [200, 200]);
                    $data['main_img'] = $img[1];
                }
               
                $result = Db::name('shop_classification')->where('id', (int)$post['id'])->update($data);
                !is_numeric($result) && exit(res_json_native(-1, '修改失败'));
                Hook::listen('admin_log', ['商品分类', '修改了类别'.$data['name']]);

                destroyFormToken($post);
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100, $e->getMessage());
            }
        }
         return res_json(-2, '请勿重复提交');
        
    }

    //添加二级分类
    public function classSecond()
    {
        $pid = (int)$this->request->get('pid');
        $pid && $info = Db::name('shop_classification')->field('name,id')->where(['id' => $pid])->find();
        isset($info) && View::assign('info', $info);
        return View::fetch();
    }

    public function secondClass(Request $request)
    {
        if(checkFormToken($request->post())){
            $validate = \think\Validate::make([
                'name' => 'require|min:2',
                'image' => 'require',
            ],[
                'name.require'=> '请填写分类名称',
                'name.min'    => '分类名称最少不能少于2个字符',
                'image.require'    => '请上传图片'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

            $image = app('upload')->base64ToThumbnailImage($request->post('image'), [100, 100]);
      
            try {
                $data = [
                    'name' => $request->post('name'),
                    'main_img' => $image[1],
                    'pid' => $request->post('id')
                ];

                $result = Db::name('shop_classification') -> insert($data);
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
        $info = Db::name('shop_classification')->field('id')->where(['pid' => $id])->find();
        if($info){
           return json(['code' => -2, 'result' => '请先删除子类']);
        }
        if (Db::name('shop_classification') ->where('id','=', $id) -> delete()) {  
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

        $count = Db::name('shop_goods_sku')->where(['goods_id' => $id, 'status' => 1])->count();
        if($count == 1){
            //同时上下架sku
            Db::name('shop_goods_sku')->where(['goods_id' => $id])->update(['is_sold' => $sold]);
        }

        return json(['code' => 1, 'result' => '成功']);
    }


   /**
     * 轮播图
     * @author zhaoyun  
     */
    public function banner()
    {
        return View::fetch();
    }

    public function bannerList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
            ['status', '=', 1],
            ['title', 'LIKE', $get['title'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);
        $countQuery = Db::name('shop_banner')->where($formWhere);
        $query = Db::name('shop_banner')->where($formWhere)->page($page, $limit)->order(['sorted', 'id'=>'desc' ]);
        $count = $countQuery->count();
        $data = $query->select();
        return table_json($data, $count);
    }

     public function bannerAdd()
    {
        return View::fetch();
    }

    public function addBanner(Request $request)
    {
        if(checkFormToken($request->post())){

            $validate = \think\Validate::make([
                'title' => 'require|min:2',
                'image' => 'require',
            ],[
                'title.require'=> '请填写标题',
                'title.min'    => '标题名称最少不能少于2个字符',
                'image.require'    => '请上传图片'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }
    
            $image = app('upload')->base64ToImage4Banner($request->post('image'), [640, 320]);
      
            try {
                $data = [
                    'title' => $request->post('title'),
                    'landing_url' => $request->post('landing_url'),
                    'img' => $image[1],
                    'status' => 1
                ];

                $result = Db::name('shop_banner') -> insert($data);
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
        $id && $info = Db::name('shop_banner')->where(['id' => $id])->find();
        isset($info) && View::assign('info', $info);
        return View::fetch();
    }


    public function editBanner(Request $request)
    {   
        if(checkFormToken($request->post())){

             $validate = \think\Validate::make([
                    'title' => 'require|min:2'
                ],[
                    'title.require'=> '请填写标题',
                    'title.min'    => '标题名称最少不能少于2个字符'
                ]);

                if(!$validate->check($request->post())){
                    return res_json(-3, $validate->getError());
                }

            try {
                $post = $request->post();
          
                $data = [
                        'title' => $request->post('title'),
                        'landing_url' => $request->post('landing_url')
                ];

                if($request->post('image')){
                    $image = app('upload')->base64ToImage4Banner($request->post('image'), [600, 340]);
                    $data['img'] = $image[1] ;
                }
                $result = Db::name('shop_banner')->where('id', (int)$post['id'])->update($data);
                !is_numeric($result) && exit(res_json_native(-1, '修改失败'));
                Hook::listen('admin_log', ['首页管理', '修改了banner']);

                destroyFormToken($post);
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100, $e->getMessage());
            }

        }

         return res_json(-2, '请勿重复提交');
        
    }

    public function bannerDel(Request $request)
    {
        
        $id = $request->post('id');
         $data = ['status' => -1];
        if (Db::name('shop_banner') ->where('id', '=', $id) -> update($data)) { 
            Hook::listen('admin_log', ['首页管理', '删除了轮播图']);
            return res_json(1); 
        } else {
            return res_json(-1,'删除失败');
        }
      
    }


    public function changeWeight()
    {
        $post = $this->request->post();

        $post['id'] && $res = Db::name('shop_banner')->where('id', '=', (int)$post['id'])->update(['sorted' => (int)$post['newVal']]);
        !$res && exit(res_json_native(-3, '修改失败'));

        return res_json(1);
    }


    /**
     * 推荐位
     * @author zhaoyun  
     */
    public function recommended()
    {
        return View::fetch();
    }

     public function recoList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;       

        $count = Db::name('shop_reco_place')->count();
        $data = Db::name('shop_reco_place')->page($page, $limit)->order(['sorted', 'id'])->select();

        return table_json($data, $count);
    }

     public function recoAdd()
    {
        return View::fetch();
    }

    public function addReco(Request $request)
    {
        if(checkFormToken($request->post())){
            $validate = \think\Validate::make([
                'name' => 'require|min:2'
            ],[
                'name.require'=> '请填写标题',
                'name.min'    => '标题名称最少不能少于2个字符'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

           try {
                $data = [
                    'name' => $request->post('name')
                ];

                $result = Db::name('shop_reco_place') -> insert($data);
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
        $id && $info = Db::name('shop_reco_place')->where(['id' => $id])->find();
        isset($info) && View::assign('info', $info);
        return View::fetch();
    }

    public function changeRecoWeight()
    {
        $post = $this->request->post();

        $post['id'] && $res = Db::name('shop_reco_place')->where('id', '=', (int)$post['id'])->update(['sorted' => (int)$post['newVal']]);
        !$res && exit(res_json_native(-3, '修改失败'));

        return res_json(1);
    }



    public function editReco(Request $request)
    {
       if(checkFormToken($request->post())){ 
             $validate = \think\Validate::make([
                'name' => 'require|min:2'
            ],[
                'name.require'=> '请填写标题',
                'name.min'    => '标题名称最少不能少于2个字符'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

            try {
                $post = $request->post();
                $data = [
                        'name' => $request->post('name')
                ];

                $result = Db::name('shop_reco_place')->where('id', (int)$post['id'])->update($data);
                !is_numeric($result) && exit(res_json_native(-1, '修改失败'));
                Hook::listen('admin_log', ['首页管理', '修改了banner']);

                destroyFormToken($post);
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100, $e->getMessage());
            }
        }
        return res_json(-2, '请勿重复提交'); 
    }

    public function recoDetail()
    {      
        $reco_id = (int)$this->request->get('id');
        
        $where = [
            // 'g.status' => 1,
            // 'g.is_sold' => 1,
            'a.rec_id' => $reco_id
        ];

        $info = Db::name('shop_reco_goods')->alias('a')->field('c.*,d.name rec_name')->leftjoin('(SELECT g.id goods_id,g.goods_name,min(s.price) price,s.market_price,s.sku_img FROM shop_goods g LEFT JOIN shop_goods_sku s ON g.id=s.goods_id WHERE g.status =1 AND g.is_sold=1 AND s.status = 1 AND s.is_sold =1 GROUP BY g.id) c', 'a.goods_id=c.goods_id')->leftjoin('shop_reco_place d', 'd.id=a.rec_id')->where('c.goods_id', 'NOT NULL')->where($where)->order(['d.sorted' ,'a.rec_id', 'a.create_time' => 'desc'])->select();

        View::assign('info', $info);
        View::assign('reco_id', $reco_id);
        return View::fetch();
    }

    public function recoDel(Request $request)
    {
        $recoId = $request->post('id');
        Db::startTrans();

        $res1 = Db::name('shop_reco_place')->where(['id' => $recoId])->delete();
        $res2 = Db::name('shop_reco_goods')->where(['rec_id'=>$recoId])->delete();
        if($res1 && $res2){
            Db::commit();
            return res_json(1);
        }

        Db::rollback();
        return res_json(-1);
    }

    public function recoGoodsDel(Request $request)
    {
        
        $gid = $request->post('gid');
        $rid = $request->post('rid');
        if (Db::name('shop_reco_goods') ->where(['goods_id'=>$gid,'rec_id'=>$rid]) -> delete()) { 
            Hook::listen('admin_log', ['首页管理', '删除了推荐位的商品']);
            return res_json(1);
        } else {
            return res_json(-1);
        }
      
    }

    public function recogoods()
    {    
        $rid = (int)$this->request->get('rid');
        View::assign('rid', $rid);

        $classifyArr = Db::name('shop_classification')->field('id,name,pid')->select();

        $tree = new \util\Tree($classifyArr);
        $classify = $tree->leaf();
        
        View::assign('classify', $classify);

        return View::fetch();
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
        $countQuery = Db::name('shop_goods')->where($formWhere);

        $query = Db::name('shop_goods')->alias('g')->leftJoin('shop_classification c','c.id = g.classification_id')->field('g.id,g.goods_name,g.goods_imgs,g.post_type,g.freight,c.name,c.pid,g.status,g.is_sold,FROM_UNIXTIME(g.create_time, "%Y-%m-%d %H:%i:%s") AS create_time')->where($formWhere)->page($page, $limit)->order('g.id', 'desc');

        $count = $countQuery->count();
        $data = $query->select();
        $rid = $get['rid'];

        foreach ($data as $k => $v) {
            $res = Db::name('shop_reco_goods')->where(['goods_id'=>$v['id'],'rec_id'=>$rid])->find();
            if($res){
                $data[$k]['fin'] = 1;
            }else{
                $data[$k]['fin'] = 2; 
            }

            $arr = explode(',',$v['goods_imgs']);
            $data[$k]['imgs'] = $arr[0];
            $pid = Db::name('shop_classification')->where('id','=',$v['pid'])->find();
            $data[$k]['classfy'] = $pid['name'].' || '.$v['name'];
           
        }

        return table_json($data, $count);
    }

    public function addrecogoods(Request $request)
    {
        
        $gid =  $data['goods_id'] = $request->post('gid');
        $rid = $data['rec_id']  = $request->post('rid');
        $data['create_time'] = date('Y-m-d H:i:s');
        $res = Db::name('shop_reco_goods')->where(['goods_id'=>$gid,'rec_id'=>$rid])->find();
        if (!$res && Db::name('shop_reco_goods') ->insert($data)) { 
            Hook::listen('admin_log', ['首页管理', '添加了推荐位的商品']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
      
    }

    public function sold()
    {
        $gid = (int)$this->request->get('id');

        $list = Db::name('shop_goods_sku')->where(['goods_id'=>$gid])->select();
        $str = '';
        foreach ($list as $k => $v) {
            $arr = json_decode($v['sku'],true);
            foreach ($arr as $key => $val) {
                $str .= $val['title'].'：'.$val['attr'].'；';
            }
            
            $list[$k]['skus'] = $str;
            $str=''; 
        }
        View::assign('data', $list);
        return View::fetch();
    }

    public function addsold()
    {
        $post = $this->request->post();

        $Arr1 = $post['idlist'];
        $Arr2 = $post['soldlist'];
        $Arr3 = $post['market_price'];
        $Arr4 = $post['pricelist'];

        foreach ($Arr1 as $k => $r) {
            $res[] = [$Arr1[$k],$Arr2[$k],$Arr3[$k],$Arr4[$k]];
        }

        foreach ($res as $k => $v) {
          
            $data['stocks']=$v[1];
            $data['market_price']=$v[2];
            $data['price']=$v[3];
            $re = Db::name('shop_goods_sku')->where(['id'=>$v[0]])->update($data);
        }
        if($re){
            return res_json(1); 
        }else{
            return res_json(-1); 
        }
        
    }

    public function goodsSku()
    {
        $classifyArr = Db::name('shop_classification')->field('id,name,pid')->select();

        $tree = new \util\Tree($classifyArr);
        $classify = $tree->leaf();
        
        View::assign('classify', $classify);
        return View::fetch();
    }

    public function goodsSkuList()
    {
        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
             ['g.status', '<>', '-1']
            ,['g.goods_name', 'LIKE', $get['goods_name'] ?? '']
            ,['g.classification_id', '=', $get['class_id'] ?? '']
        ];

        if(isset($get['is_sold'])){
            if($get['is_sold'] == 1){
                $where[] = ['s.is_sold', '=', 1];
                $where[] = ['g.is_sold', '=', 1];
            }else if($get['is_sold'] === '0'){
                $where[] = ['s.is_sold|g.is_sold', '=', 0];
            }
        }
 
        $formWhere = $this->parseWhere($where);
        $count = Db::name('shop_goods_sku')->alias('s')->leftjoin('shop_goods g', 's.goods_id=g.id')->where($formWhere)->count();

        $data =Db::name('shop_goods_sku')->alias('s')->field('s.*,g.goods_name,c.name cname,cc.name ccname,g.is_sold main_sold')->leftjoin('shop_goods g', 's.goods_id=g.id')->leftJoin('shop_classification c','c.id = g.classification_id')->leftJoin('shop_classification cc', 'c.pid=cc.id')->where($formWhere)->page($page, $limit)->order('g.id', 'desc')->select();
     
        return table_json($data, $count);
    }

    public function changeSkuSold()
    {
        $id = (int)$this->request->post('id');
        $goods_id = (int)$this->request->post('goods_id');

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

        $count = Db::name('shop_goods_sku')->where(['goods_id' => $goods_id, 'status' => 1, 'is_sold'=> 1])->count();
        if($count == 1){
            //同时上下架商品
            Db::name('shop_goods')->where(['id' => $goods_id])->update(['is_sold' => $sold]);
        }

        $id && $res = Db::name('shop_goods_sku')->where('id', '=', $id)->update(['is_sold' => $sold]);
        !$res && exit(json(['code' => -1, 'result' => '失败']));

        return json(['code' => 1, 'result' => '成功']);
    }

    public function skuSet()
    {
        $id = $this->request->get('id');
        $skuInfo = Db::name('shop_goods_sku')->where(['id' => (int)$id])->field('id,stocks,price,market_price')->find();

        View::assign('sku', $skuInfo);
        return View::fetch();
    }

    public function modifySku()
    {
        $post = $this->request->post();

        $data = [
            'price' => (float)$post['price'],
            'market_price' => (float)$post['market_price'],
            'stocks' => (int)$post['stocks']
        ];

        $res = Db::name('shop_goods_sku')->where(['id' => (int)$post['sku_id']])->update($data);
        !$res && exit(res_json_native(-1, '系统错误'));

        return res_json(1);
    }

    public function skuDel()
    {
        $sku_id = (int)$this->request->post('id');
        // $del = Db::name('shop_goods_sku')->where(['id' => $sku_id])->update(['status' => -1]);
        // dump($del);

        $skuInfo = Db::name('shop_goods_sku')->alias('s')->field('s.id AS sku_id,s.goods_id,s.sku,g.goods_sku_attributes')->join('shop_goods g', 's.goods_id=g.id')->where(['s.id' => $sku_id])->find();
        dump($skuInfo);

        $skuS = Db::name('shop_goods_sku')->where(['goods_id' => $skuInfo['goods_id']])->count();
        // if(1 == count($skuS)){
            //若果只有一个SKU直接删除商品
            // Db::name('shop_goods')->where(['id' => $skuInfo['goods_id']])->update(['status' => -1]);
        // }else{
            $sku = json_decode($skuInfo['sku'], true);
            $skuArr = json_decode($skuInfo['goods_sku_attributes'], true);
            
            dump($sku);
            dump($skuArr);
        // }
    }
}