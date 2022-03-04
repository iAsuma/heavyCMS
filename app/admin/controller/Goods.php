<?php
namespace app\admin\controller;
use think\facade\Db;
use think\facade\View;
use think\Request;
use think\facade\Hook;
/**
 * 商品创建与编辑
 * @author asuma(li shuaiqiu)
 */
class Goods extends Base
{
	
	public function create()
	{
		$classifyArr = Db::table('shop_classification')->field('id,name,pid')->select();

		$tree = new \util\Tree($classifyArr);
		$classify = $tree->leaf();
		
		View::assign('classify', $classify);
		return View::fetch();
	}

	public function save(Request $request)
	{
		if(checkFormToken($request->post())){
            $validate = \think\Validate::make([
                'goods_name' => 'require|max:150',
                'goods_attributes' => 'require',
                'introduction' => 'max:200',
                'goods_imgs' => 'require',
                'description' => 'max:8000'
            ],[
                'title.require'=> '请填写商品名称',
                'title.max'    => '商品名称最多不能超过150个字符',
                'goods_attributes.require' => '请完善商品属性',
                'introduction.max' => '商品介绍最多不能超过200个字符',
                'goods_imgs.require' => '请上传商品图片',
                'description.max' => '商品描述最多不能超过8000个字符',
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

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
						'sku_img' => $skus_detail[$k]['img'] ?? '',
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
		
		View::assign('imgArr', $imgArr);
		View::assign('max', $max);
		View::assign('idx', $upIdx);
		return View::fetch();
	}

	public function pictureList()
	{
		$post = $this->request->post();

        $page = $post['page'] ?? 1;
        $limit = $post['limit'] ?? 16;
        
        $count = Db::table('shop_goods_pics')->count();
        $list = Db::table('shop_goods_pics')->page($page, $limit)->order('id', 'desc')->select();

        $page = ceil($count/$limit);
 
        return res_json(1, [$page, $list]);
	}

	public function uploadMainImg()
	{
	    $file = app('upload')->file('image');
	    $info = app('upload')->action($file, [800, 800], true, \think\Image::THUMB_SCALING);

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

		!$id && exception('商品不存在');

		$info = Db::table('shop_goods')->where($where)->find();
		$info['goodsImgArr'] = explode(',', $info['goods_imgs']);
		$info['goods_attrs'] = json_decode($info['goods_sku_attributes'], true);

		$skus = Db::table('shop_goods_sku')->field('sku,price,market_price,sku_img,stocks')->where(['goods_id' => (int)$id,
			'status' => 1])->select();

		foreach ($info['goods_attrs'] as $v) {
			$attrs[$v[0]] = $v[1];
		}
		$info['attrs_str'] = json_encode($attrs, JSON_UNESCAPED_UNICODE);

		$skus_arr_json = [];
		$skus_val = [];
		foreach ($skus as $v) {
			$skus_arr_json[] = json_decode($v['sku'], true);
			$skus_val[] = [
				'stock' =>  (string)$v['stocks'],
				'marketPrice' => $v['market_price'],
				'price' => $v['price'],
				'img' => $v['sku_img']
			];
		}

		$info['skus_str'] = json_encode($skus_arr_json, JSON_UNESCAPED_UNICODE);
		$info['skus_val'] = json_encode($skus_val, JSON_UNESCAPED_SLASHES );

		View::assign('classify', $classify);
		View::assign('goods', $info ?? []);
		View::assign('skus', $skus);
		View::assign('skus_arr', $skus_arr_json);
		return View::fetch();
	}

	public function modify(Request $request)
	{
		if(checkFormToken($request->post())){
            $validate = \think\Validate::make([
                'goods_name' => 'require|max:150',
                'goods_attributes' => 'require',
                'introduction' => 'max:200',
                'goods_imgs' => 'require',
                'description' => 'max:8000'
            ],[
                'title.require'=> '请填写商品名称',
                'title.max'    => '商品名称最多不能超过150个字符',
                'goods_attributes.require' => '请完善商品属性',
                'introduction.max' => '商品介绍最多不能超过200个字符',
                'goods_imgs.require' => '请上传商品图片',
                'description.max' => '商品描述最多不能超过8000个字符',
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

            Db::startTrans();
            try {
            	$attrsArr = json_decode($request->post('goods_attributes'), true);
				$attr = [];
				foreach ($attrsArr as $k => $v) {
					$attr[] = [$k, $v];
				}

                $data = [
                    'goods_name' => $request->post('goods_name'),
                    'classification_id' => $request->post('classification'),
                    'goods_sku_attributes' => json_encode($attr, JSON_UNESCAPED_UNICODE),
                    'introduction' => $request->post('introduction'),
                    'is_sold' => (int)$request->post('is_sold'),
                    'goods_imgs' => $request->post('goods_imgs'),
                    'description' => $request->post('description'),
                    'status' => 1,
                    'post_type' => (int)$request->post('post_type'),
                    'freight' => $request->post('post_type') == 2 ? $request->post('freight') : 0
                ];

                $request->post('gid') && $res = Db::name('shop_goods') ->where(['id' => $request->post('gid')]) -> update($data);
                !is_numeric($res) && exit(res_json_native(-6, '修改失败'));

                $skus = json_decode($request->post('goods_skus'), true);
				$skus_detail = json_decode($request->post('skus_val'), true);
				
				$skusArr = [];
				$result = false;
				foreach ($skus as $k=>$v) {
					$sku = json_encode($v, JSON_UNESCAPED_UNICODE);
					$mark = Db::name('shop_goods_sku')->where(['goods_id' => $request->post('gid'), 'sku' => $sku])->find();

					if($mark){
						$skusArr = [
							'price' => $skus_detail[$k]['price'],
							'market_price' => $skus_detail[$k]['marketPrice'],
							'sku_img' => $skus_detail[$k]['img'] ?? '',
							'stocks' => $skus_detail[$k]['stock'],
						];

                		$result = Db::name('shop_goods_sku')->where(['goods_id' => $request->post('gid'),'sku' => $sku])->update($skusArr);
					}else{
						$skusArr = [
							'goods_id' => $request->post('gid'),
							'sku' => json_encode($v, JSON_UNESCAPED_UNICODE),
							'is_sold' => 1,
							'price' => $skus_detail[$k]['price'],
							'market_price' => $skus_detail[$k]['marketPrice'],
							'sku_img' => $skus_detail[$k]['img'] ?? '',
							'stocks' => $skus_detail[$k]['stock'],
							'status' => 1,
							'create_time' => time()
						];

						$result = Db::name('shop_goods_sku')->insert($skusArr);
					}
					if(!is_numeric($result)){
						break;
					}
				}
				
                if(!is_numeric($result)){
                    Db::rollback();
                    return res_json(-4, '修改失败');
                }

                Hook::listen('admin_log', ['商品管理', '修改了商品']);
                
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
}