<?php
namespace app\shop\controller;
use think\Request;
use think\Db;
use wechat\facade\Loader as WeChat;
use think\facade\Cache;
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

		//5分钟后页面失效
		$interval = (time() - $buy[1])/60;
		if($interval > 5){
			$this->redirect('/shop');
		}
		
		if($buy[0] == 3){
			$this->fromBuyNow($buy);
		}else if($buy[0] == 2){
			$this->fromCart($buy);
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
		$goods = Db::table('shop_goods_sku')->field('s.goods_id,s.id sku_id,g.goods_name,g.freight,s.sku,s.price,s.sku_img,s.stocks')->alias('s')->leftjoin('shop_goods g', 's.goods_id=g.id')->where($where)->find();
		
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

	private function fromCart($buy)
	{
		$selected = explode('-', $buy[3]);

		foreach ($selected as $k=>$v) {
			//替换原数组
			$selected[$k] = explode('.', $v);
		}
		
		$cart_ids = '';
		$goods_ids = '';
		$goods_sku_ids = '';
		foreach ($selected as $v) {
			$newSelected[$v[2]]	= $v;
			$cart_ids .= $v[0].',';
			$goods_ids .= $v[1].',';
			$goods_sku_ids .= $v[2].',';
		}
		$cart_ids = rtrim($cart_ids, ',');
		$goods_ids = rtrim($goods_ids, ',');
		$goods_sku_ids = rtrim($goods_sku_ids, ',');

		/******与数据库数据比对，防止人为串改***/
		$cart_info = Db::table('shop_shopping_cart')->where('user_id', '=', $this->userId)->where('id', 'IN', $cart_ids)->select();
		foreach ($cart_info as $v) {
			if($v['goods_id'] != $newSelected[$v['goods_sku_id']][1] || $v['goods_sku_id'] != $newSelected[$v['goods_sku_id']][2] || $v['goods_num'] != $newSelected[$v['goods_sku_id']][3]){
				$this->redirect('/shop');
				break;
			}
		}
		/************/

		$where = [
			['g.status' , '=', 1],
			['s.status' , '=', 1],
			['s.goods_id', 'IN', $goods_ids],
			['s.id', 'IN', $goods_sku_ids]
		];

		$goods = Db::table('shop_goods_sku')->field('s.goods_id,s.id sku_id,g.goods_name,g.freight,s.sku,s.price,s.sku_img,s.stocks')->alias('s')->leftjoin('shop_goods g', 's.goods_id=g.id')->where($where)->select();
		foreach ($goods as &$v) {
			$v['buynum'] = $newSelected[$v['sku_id']][3];
			$sku = json_decode($v['sku'], true);
			$sku_str = '';
			foreach ($sku as $i) {
				$sku_str .= $i['attr'].',';
			}
			$v['sku'] = trim($sku_str, ',');
		}

		//查询最大运费作为最终运费
		$forFreight = Db::table('shop_goods')->field('freight')->where('id', 'IN', $goods_ids)->order('freight', 'desc')->find();

		$this->assign('goods', $goods);
		$this->assign('freight', $forFreight['freight']);
		$this->assign('cart_ids', $cart_ids);
	}

	private function getUserAddress($buy)
	{	
		if($buy[2]){
			$address = $this->userAddressById($buy[2]);
			if(empty($address)){
				$address = $this->userDefaultAddress();	
			}
		}else{
			$address = $this->userDefaultAddress();
		}
		
		$this->assign('address', $address);
	}

	/*
	* 生成订单
	*/
	public function make(Request $request)
	{
		$buyList = $request->post('buy_list');
		$freight = (int)$request->post('freight');
		$orderPrice = (float)$request->post('order_price');
		$addressId = (int)$request->post('address_id');
		$orderTime = time();

		$address = $this->userAddressById($addressId);
		$useAddress = $address['province'].$address['city'].$address['district'].$address['address'];

		Db::startTrans();
		try {
			$data1 = [
				'order_no' => $orderTime,//临时订单号
				'price' => $orderPrice,
				'user_id' => $this->userId,
				'create_time' => $orderTime,
				'receiver_name' => $address['name'],
				'receiver_phone' => $address['phone'],
				'receiver_address' => $useAddress,
				'post_type_str' => $freight ? '买家承担' : '免邮',
				'order_status' => 0,
				'status' => 1,
				'freight' => $freight
			];

			$orderId = Db::table('shop_order')->insertGetId($data1);
			if(!$orderId){
				Db::rollback();	
				return res_json(-1);
			}

			//生成实际订单号
			$orderNo = make_order_no($orderId);
			$res = Db::table('shop_order')->where('id', '=', $orderId)->update(['order_no'=>$orderNo]);
			if(!$res){
				Db::rollback();	
				return res_json(-2);
			}			

			$data2 = [];
			foreach ($buyList as $v) {
				$data2[] = [
					'order_no' => $orderNo,
					'goods_name' => $v['goods_name'],
					'goods_sku' => $v['sku'],
					'goods_img' => $v['sku_img'],
					'unit_price' => $v['price'],
					'goods_num' => $v['buynum'],
					'create_time' => $orderTime,
					'user_id' => $this->userId,
					'goods_id' => $v['goods_id'],
					'goods_sku_id' => $v['sku_id']
				];
			}
			$detail = Db::table('shop_order_detail')->insertAll($data2);
			if($detail != count($data2)){
				Db::rollback();	
				return res_json(-3);
			}

			//删除购物车中的相应商品
			if($request->post('cart_ids')){
				Db::table('shop_shopping_cart')->where('id', 'IN', $request->post('cart_ids'))->delete();
			}

			Db::commit();
			return res_json(1, $orderNo);
		} catch (\Exception $e) {
			Db::rollback();
            return res_json(-4, '系统错误');
		}
	}

	/*
	* 订单详情
	*/
	public function detail($no)
	{
		if(empty($no)){
			$this->redirect('/shop');
		}

		$orderBase = Db::table('shop_order')->where('order_no', '=', $no)->where('status', '=', 1)->find();
		if(empty($orderBase)){
			$this->redirect('/shop');	
		}

		$orderDetail = Db::table('shop_order_detail')->where('order_no', '=', $no)->select();

		$this->assign('order', [$orderBase, $orderDetail]);
		$this->assign('payJsConfig', $this->payJsConfig());
		return $this->fetch();
	}

	/*
	* 生成微信预支付订单
	*/
	protected function makeWxPreOrder($order)
	{
		$prepayKey = $this->wx_openid.'_'.$order['order_no'];

		if(Cache::get($prepayKey)){
			return Cache::get($prepayKey);
		}

		$result = WeChat::unifyMakeOrder([
		    'out_trade_no' => $order['order_no'],
		    'total_fee' => $order['price']*100,
		    'notify_url' => url('Api/wxPayNotice', '', '', true), 
		    'openid' => $this->wx_openid,
		]);

		if($result[0] == 1){
			Cache::set($prepayKey, $result[1], 6600); //设置有效期在2小时以内
			return $result[1];
		}

		return false;
	}

	/*
	* 微信支付JS config
	*/
	public function payJsConfig()
	{
		$config = WeChat::jsConfig(['chooseWXPay'], $debug = false);
		
		return $config;
	}
	/*
	* 发起支付
	*/
	public function pay(Request $request)
	{
		$order_no = $request->post('order');
		$where = [
			['order_no', '=', $order_no],
			['status', '=', 1],
			['order_status', '=', 0],
			['user_id', '=', $this->userId]
		];
		$orderInfo = Db::table('shop_order')->field('id,order_no,price')->where($where)->find();
		!$orderInfo && exit(res_json_native(-3, '订单错误'));

		//获取预支付订单
		$payOrder = $this->makeWxPreOrder([
			'order_no' => $order_no,
			// 'price' => $orderInfo['price']
			'price' => 0.01
		]);
		!$payOrder && exit(res_json_native(-1, '支付系统错误'));

		//返回支付配置
		$payment = WeChat::payment();
		$jssdk = $payment->jssdk;
		
		$config = $jssdk->sdkConfig($payOrder['prepay_id']); 
		!$config && exit(res_json_native(-2, '微信支付启动失败'));

		return res_json(1, $config);
	}

	public function getOrderStatus(Request $request)
	{
		$order_no = $request->post('order');
		$where = [
			['order_no', '=', $order_no],
			['status', '=', 1],
			['user_id', '=', $this->userId]
		];

		$orderInfo = Db::table('shop_order')->field('id,order_no,order_status')->where($where)->find();
		!$orderInfo && exit(res_json_native(-1));

		if($orderInfo['order_status'] == 1){
			return res_json(1);
		}else if($orderInfo['order_status'] == 0){
			return res_json(2);
		}else{
			return res_json(-2);
		}

		
	}
}