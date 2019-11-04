<?php
namespace app\shop\controller;
use think\Db;
/**
 * 购物车
 */
class Cart extends Base
{
	
	public function index()
	{
		// $this->userId = 1;
		$cart = Db::table('shop_shopping_cart')->field('c.*,s.price,s.stocks')->alias('c')->join('shop_goods_sku s', 'c.goods_sku_id=s.id')->join('shop_goods g', 's.goods_id=g.id')->where(['s.status' => 1, 'g.status' => 1, 'user_id' => $this->userId])->select();
		
		$this->assign('cart', $cart);
		return $this->fetch();
	}

	public function changeStock()
	{
		// dump($this->request->post());
		$id = $this->request->post('cart_id');
		$num = $this->request->post('goods_num');

		$res = Db::table('shop_shopping_cart')->where(['id' => $id, 'user_id' => $this->userId])->update(['goods_num' => $num]);
		!$res && exit(res_json_native(-1));
		return res_json(1);
	}
}