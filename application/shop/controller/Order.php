<?php
namespace app\shop\controller;
use think\Request;
use think\Db;
/**
 * 订单相关
 */
class Order extends Base
{
	/*
	* 订单预览
	*/
	public function preSure(Request $request)
	{
		if(!$request->get('buy')){
			$this->redirect('/shop');
		}

		$buy = explode('_', $request->get('buy'));

		$interval = (time() - $buy[2])/60;
		if($interval > 5){
			// $this->redirect('/shop');
		}
		
		if($buy[0] == 3){
			$this->fromBuyNow($buy);
		}else if($buy[0] == 2){

		}else{
			$this->redirect('/shop');
		}

		$this->getUserAddress($buy);

		$this->assign('from', $buy[0]);
		return $this->fetch();
	}

	private function fromBuyNow($buy)
	{
		$where = [
			['g.status' , '=', 1],
			['s.status' , '=', 1],
			['s.goods_id', '=', $buy[3]],
			['s.id', '=', $buy[5]]
		];
		$goods = Db::table('shop_goods_sku')->field('g.goods_name,g.freight,s.sku,s.price,s.sku_img,s.stocks')->alias('s')->leftjoin('shop_goods g', 's.goods_id=g.id')->where($where)->find();
		
		$sku = json_decode($goods['sku'], true);
		$sku_str = '';
		foreach ($sku as $v) {
			$sku_str .= $v['attr'].',';
		}
		$goods['sku'] = trim($sku_str, ',');
		$goods['buynum'] = $buy[4];

		$this->assign('goods', [$goods]);
		$this->assign('freight', $goods['freight']);
	}

	private function getUserAddress($buy)
	{	
		if($buy[2]){
			$address = Db::table('shop_receiver_address')->where(['user_id' => $this->userId, 'id' => $buy[2]])->find();
			if(empty($address)){
				$address = $this->userDefaultAddress();	
			}
		}else{
			$address = $this->userDefaultAddress();
		}
		
		$this->assign('address', $address);
	}
}