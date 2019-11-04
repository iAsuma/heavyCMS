<?php
namespace app\shop\controller;
use think\Request;
use think\Db;
/**
 * 商品
 */
class Goods extends Base
{
	
	public function detail(Request $request)
	{
		$id = (int)$request->param('id');
		if(empty($id)){
			return $this->fetch('index@public/tips', ['type' => 'little', 'code' => '商品不存在']);
		}

		$where = [
			'g.id' => $id,
			'g.status' => 1,
			'g.is_sold' => 1
		];

		$info = Db::table('shop_goods')->alias('g')->field('g.*,s.price,s.market_price,s.stocks')->join('(SELECT a.*,b.market_price,b.stocks FROM (SELECT goods_id,MIN(price) as price FROM shop_goods_sku WHERE status = 1 AND is_sold = 1 GROUP BY goods_id)a,shop_goods_sku b WHERE a.price=b.price AND a.goods_id=b.goods_id) s', 'g.id=s.goods_id')->where($where)->find();

		if(empty($info)){
			return $this->fetch('index@public/tips', ['type' => 'little', 'code' => '商品不存在']);
		}

		$info['attrs'] = json_decode($info['goods_sku_attributes'], true);

		$info['main_img'] = explode(',', $info['goods_imgs']);
		$info['imgcount'] = count($info['main_img']);

		$info['sold'] = Db::table('shop_order_detail')->alias('a')->leftjoin('shop_order b', 'a.order_no=b.order_no')->where(['a.user_id' => $this->userId, 'a.goods_id' => $id])->where('b.order_status', 'IN', '1,2,3,5,6')->count();

		$info['collectd'] = $this->isCollected($id);
		
		$this->assign('info', $info);
		return $this->fetch();
	}

	public function doCollected()
	{
		$goods_id = $this->request->post('id');
		$mark = $this->request->post('mark');

		if($mark == 1 && $this->isCollected($goods_id)){
			$res = Db::table('user_goods_collection')->where(['user_id' => $this->userId, 'goods_id' => $goods_id])->delete();
		}else{
			$res = Db::table('user_goods_collection')->insert(['user_id' => $this->userId, 'goods_id' => $goods_id, 'create_time' => date('Y-m-d H:i:s')]);
		}

		if($res){
			return res_json(1);
		}else{
			return res_json(-1);
		}
	}

	public function getAttrInfo(Request $request)
	{
		$goods_id = $request->post('goods_id');
		$attrs = $request->post('attrs');
		
		$query = Db::table('shop_goods_sku')->field('price,stocks,sku_img')->where('goods_id', '=', $goods_id);

		foreach ($attrs as $v) {
			$query->where('sku', 'LIKE', '%"'.$v.'"%');
		}

		$sku = $query->find();

		return res_json(1, $sku);
	}

	public function pushToCart(Request $request)
	{
		$goods_id = $request->post('goods_id');
		$attrs = $request->post('attrs');
		$buyNum = $request->post('buyNum');

		$query = Db::table('shop_goods_sku')->field('g.goods_name,s.id,s.sku_img')->alias('s')->join('shop_goods g', 's.goods_id=g.id')->where('s.goods_id', '=', $goods_id)->where('s.status', '=', 1)->where('g.status', '=', 1);

		foreach ($attrs as $v) {
			$query->where('sku', 'LIKE', '%"'.$v.'"%');
		}

		$sku = $query->find();
		empty($sku) && exit(res_json_native(-1, '商品不存在'));

		$isExist = Db::table('shop_shopping_cart')->where(['user_id' => $this->userId, 'goods_id' => $goods_id, 'goods_sku_id' => $sku['id']])->find();

		if($isExist){
			$data = [
				'goods_num' => Db::raw('goods_num+'.$buyNum)
			];
			$res = Db::table('shop_shopping_cart')->where(['id' => $isExist['id']])->update($data);
			// dump($res);die;
		}else{
			$data = [
				'user_id' => $this->userId,
				'goods_id' => $goods_id,
				'goods_sku_id' => $sku['id'],
				'goods_name' => $sku['goods_name'],
				'goods_img' => $sku['sku_img'],
				'goods_sku' => implode(',', $attrs),
				'goods_num' => $buyNum
			];

			$res = Db::table('shop_shopping_cart')->insert($data);	
		}
		
		!$res && exit(res_json_native(-2, '加入失败'));
		
		return res_json(1, '添加成功');
	}
}