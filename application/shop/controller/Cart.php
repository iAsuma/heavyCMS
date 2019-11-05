<?php
namespace app\shop\controller;
use think\Db;
use think\Request;
/**
 * 购物车相关
 */
class Cart extends Base
{	
	/**
	 * 添加商品到购物车
	 */
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

	public function index()
	{
		// $this->userId = 1;
		$cart = Db::table('shop_shopping_cart')->field('c.*,s.price,s.stocks')->alias('c')->join('shop_goods_sku s', 'c.goods_sku_id=s.id')->join('shop_goods g', 's.goods_id=g.id')->where(['s.status' => 1, 'g.status' => 1, 'user_id' => $this->userId])->select();
		
		$this->assign('cart', $cart);
		return $this->fetch();
	}

	/**
	 * 修改库存
	 */
	public function changeStock(Request $request)
	{
		$id = $request->post('cart_id');
		$num = $request->post('goods_num');

		$res = Db::table('shop_shopping_cart')->where(['id' => $id, 'user_id' => $this->userId])->update(['goods_num' => $num]);
		!$res && exit(res_json_native(-1));
		return res_json(1);
	}
}