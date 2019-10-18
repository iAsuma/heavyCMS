<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\facade\Hook;
/**
 * 商品创建与编辑
 */
class Goods extends Base
{
	
	public function create()
	{
		$classifyArr = Db::table('shop_classification')->field('id,name,pid')->select();

		$tree = new \util\Tree($classifyArr);
		$classify = $tree->leaf();
		
		$this->assign('classify', $classify);
		return $this->fetch();
	}

	public function save(Request $request)
	{
		if(checkFormToken($request->post())){
            // $validate = new \app\admin\validate\Register;
            // if(!$validate->scene('register')->check($request->post())){
            //     exit(res_json_str(-1, $validate->getError()));
            // }

            Db::startTrans();
            try {
            	$attrsArr = json_decode($request->post('goods_attributes'), true);
				$attr = [];
				foreach ($attrsArr as $k => $v) {
					$attr[] = [$k, $v];
				}

				$now_time = time();
                $data = [
                    'goods_name' => $request->post('goods_name'),
                    'classification_id' => $request->post('classification'),
                    'goods_sku_attributes' => json_encode($attr, JSON_UNESCAPED_UNICODE),
                    'introduction' => $request->post('introduction'),
                    'create_time' => $now_time,
                    'is_sold' => (int)$request->post('is_sold'),
                    'goods_imgs' => $request->post('goods_imgs'),
                    'description' => $request->post('description'),
                    'status' => 1,
                    'post_type' => (int)$request->post('post_type'),
                    'freight' => $request->post('post_type') == 2 ? $request->post('freight') : 0
                ];

                $new_id = Db::name('shop_goods') -> insertGetId($data);
                !$new_id && exit(res_json_native(-6, '添加失败'));

                $skus = json_decode($request->post('goods_skus'), true);
				$skus_detail = json_decode($request->post('skus_val'), true);
				
				$skusArr = [];
				foreach ($skus as $k=>$v) {
					$skusArr[] = [
						'goods_id' => $new_id,
						'sku' => json_encode($v, JSON_UNESCAPED_UNICODE),
						'is_sold' => 1,
						'price' => $skus_detail[$k]['price'],
						'market_price' => $skus_detail[$k]['marketPrice'],
						'sku_img' => $skus_detail[$k]['img'],
						'stocks' => $skus_detail[$k]['stock'],
						'status' => 1,
						'create_time' => $now_time
					];
				}

                $result = Db::name('shop_goods_sku')->insertAll($skusArr);
                if(!$result){
                    Db::rollback();
                    return res_json(-4, '添加失败');
                }

                Hook::listen('admin_log', ['商品管理', '添加了新商品']);
                
                Db::commit();
                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                Db::rollback();
                return res_json(-5, '系统错误');
            }
        }

        return res_json(-2, '请勿重复提交');
	}

	public function pictures()
	{
		$imgs = $this->request->post('imgs');
		$max = $this->request->post('max') ?? 5;
		$upIdx = (int)$this->request->post('upIdx');

		$imgArr = $imgs ? explode(',', $imgs) : [];
		
		$this->assign('imgArr', $imgArr);
		$this->assign('max', $max);
		$this->assign('idx', $upIdx);
		return $this->fetch();
	}

	public function pictureList()
	{
		$get = $this->request->get();

        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 16;
        
        $count = Db::table('shop_goods_pics')->count();
        $list = Db::table('shop_goods_pics')->page($page, $limit)->order('id', 'desc')->select();

        $page = ceil($count/$limit);
 
        return res_json(1, [$page, $list]);
	}

	public function uploadMainImg()
	{
	    $file = app('upload')->file('image');
	    $info = app('upload')->action($file);

	    !$info && exit(res_json_native(-1, '上传失败'));

	    Db::table('shop_goods_pics')->insert(['good_img_url' => $info[0]]);

	    return res_json(1, $info);
	}

	public function deleteGoodsPic()
	{
		$imgUrl = $this->request->post('imgUrl');
		$img = str_replace('/uploads/thumb', '', $imgUrl);
		
		$res = Db::table('shop_goods_pics')->where('good_img_url', $img)->delete();

		if($res){
			$env_path = env('FILE_ROOT_PATH').env('FILE_UPLOAD_PATH');
			$fullPath = $env_path.$img;
			$thumbPath = $env_path.DIRECTORY_SEPARATOR.'thumb'.$img;
			@unlink($fullPath);
			@unlink($thumbPath);

			return res_json(1);
		}

		return res_json(-1);
	}

	public function edit(int $id)
	{
		$classifyArr = Db::table('shop_classification')->field('id,name,pid')->select();

		$tree = new \util\Tree($classifyArr);
		$classify = $tree->leaf();

		$where = [
			'id' => (int)$id,
			'status' => 1
		];
		$id && $info = Db::table('shop_goods')->where($where)->find();

		$this->assign('classify', $classify);
		$this->assign('goods', $info ?? []);
		return $this->fetch();
	}
}