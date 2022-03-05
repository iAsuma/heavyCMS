<?php
namespace app\shop\controller;
use think\facade\Db;
use think\facade\View;

/**
 * 用户操作相关及个人中心(我的)
 * @author asuma(lishuaiqiu)
 */
class User extends Base
{
	/*
	* 商品收藏
	*/
	public function dogGoodsCollected()
	{
		$goods_id = $this->request->post('id');
		$mark = $this->request->post('mark');

		if($mark == 1 && $this->isCollected($goods_id)){
			$res = Db::name('user_goods_collection')->where(['user_id' => $this->userId, 'goods_id' => $goods_id])->delete();
		}else{
			$res = Db::name('user_goods_collection')->insert(['user_id' => $this->userId, 'goods_id' => $goods_id, 'create_time' => date('Y-m-d H:i:s')]);
		}

		if($res){
			return res_json(1);
		}else{
			return res_json(-1);
		}
	}

	/*
	* 首页
	*/
	public function index()
	{
		$report = $this->orderCount();

		View::assign('report', $report);
		return View::fetch();
	}

	/**
	* 订单管理相关
	*/
	public function orderCount()
	{
		$uid = $this->userId;
		$where = [
			'user_id' => $this->userId,
			'status' => 1
		];

		$data['nopay'] = Db::name('shop_order')->where($where)->where(['order_status' => 0])->count();
		$data['nosend'] = Db::name('shop_order')->where($where)->where(['order_status' => 1])->count();
		$data['noget'] = Db::name('shop_order')->where($where)->where(['order_status' => 2])->count();
		
		return $data;
	}

	public function order()
	{
		$type = $this->request->get('o');
		($type == '' || !in_array($type, [0,1,2,3])) && $type = 'all';

		View::assign('type', $type);
		return View::fetch();
	}

	public function orderList()
	{
		$page = $this->request->post('pageStart') ?: 1;
		$limit = $this->request->post('perpage') ?: 8;
		$status = $this->request->post('type');

		$where = [
			['status', '=', 1],
			['user_id', '=', $this->userId]
		];

		switch ($status) {
			case '1':
				$where[] = ['order_status', '=', 1];
				break;
			case '2':
				$where[] = ['order_status', '=', 2];
				break;
			case '3':
				$where[] = ['order_status', '=', 3];
				break;
			case '0':
				$where[] = ['order_status', '=', 0];
				break;
			default:
				break;
		}

		$table = Db::name('shop_order')->where($where)->order('id', 'desc')->page($page, $limit)->buildSql();

		$list = Db::name($table)->alias('o')->field('o.order_no,o.price,o.pay_money,o.order_status,d.goods_name,d.goods_sku,d.goods_img,d.unit_price,d.goods_num')->leftjoin('shop_order_detail d', 'o.order_no=d.order_no')->where('d.status', '=', 1)->order('o.id', 'desc')->select();

		$result = [];
		foreach ($list as $v) {
			$result[$v['order_no']][0]['order_no'] = $v['order_no'];
			$result[$v['order_no']][0]['price'] = $v['price'];
			$result[$v['order_no']][0]['pay_money'] = $v['pay_money'];
			$result[$v['order_no']][0]['order_status'] = $v['order_status'];
			$result[$v['order_no']][1][] = [
				'goods_name' => $v['goods_name'],
				'goods_sku' => $v['goods_sku'],
				'goods_img' => $v['goods_img'],
				'unit_price' => $v['unit_price'],
				'goods_num' => $v['goods_num'],
			];
		}

		empty($result) && exit(res_json_native(-1));

		return res_json(1, array_values($result));
	}

	public function changeOrderStatus()
	{
		$orderNo = $this->request->post('order_no');
		$status = $this->request->post('status');

		$data = [
			'complete_time' => time()
		];

		if($status == '-1'){
			$data['status'] = $status;
		}else if($status == '11'){
			$data['order_status'] = $status;
			$data['complete_time'] = NULL;
			$order = Db::name('shop_order')->where(['order_no' => $orderNo, 'user_id' => $this->userId])->find();
			$refund = [
				'return_order_no' => '',
				'order_no' => $orderNo,
				'refund_fee' => $order['pay_money'],
				'user_id' => $this->userId,
				'create_time' => time(),
				'type' => 1,
				'status' => 0
			];

			$refundId = Db::name('shop_order_return')->insertGetId($refund);
			if($refundId){
				Db::name('shop_order_return')->where(['id' => $refundId])->update(['return_order_no' => $orderNo.str_pad($refundId, 4, 0, STR_PAD_LEFT)]);
			}
			if(!$refundId) return res_json(-2);
		}else if($status == '12'){
			$data['order_status'] = 1;

			$del = Db::name('shop_order_return')->where(['order_no' => $orderNo, 'user_id' => $this->userId])->delete();
			if(!$del) return res_json(-3);
		}else{
			$data['order_status'] = $status;
		}

		$res = Db::name('shop_order')->where(['order_no' => $orderNo, 'user_id' => $this->userId])->update($data);
		!$res && exit(res_json_native(-1));

		return res_json(1);
	}

	public function refund()
	{
		return View::fetch();
	}

	/**
	* 评价
	*/
	public function reviews()
	{
		$orderNo = $this->request->get('no');

		if(!$orderNo){
			$this->redirect('/shop');
		}

		$where = [
			'order_no' => $orderNo, 
			'user_id' => $this->userId, 
			'status' => 1,
			'order_status' => 3
		];
		$order = Db::name('shop_order')->field('id')->where($where)->find();
		if(!$order){
			$this->redirect('/shop');
		}

		View::assign('order', $order);
		View::assign('orderNo', $orderNo);
		return View::fetch();
	}

	public function uploadReviewsImg()
	{
		$file = app('upload')->file('image');
		$info = app('upload')->action($file , [1000, 1000]);
		
		if($info){
	        return res_json(1, $info);
	    }else{
	        return res_json(-1);
	    }
	}

	public function makeReviews()
	{
		$post = $this->request->post();
		$goods = Db::name('shop_order')->alias('o')->field('o.id,d.goods_id,d.goods_sku_id')->leftjoin('shop_order_detail d', 'o.order_no=d.order_no')->where(['o.id' => $post['order_id']])->select();

		$data = [];

		foreach ($goods as $v) {
			$data[] = [
				'content' => $post['content'],
				'user_id' => $this->userId,
				'imgs' => $post['imgs'],
				'order_id' => $v['id'],
				'goods_id' => $v['goods_id'],
				'goods_sku_id' => $v['goods_sku_id'],
				'stars' => $post['stars'],
				'is_anonymous' => $post['is_anonymous'] ?? 0,
				'create_time' => date('Y-m-d H:i:s')
			];
		}

		Db::startTrans();

		$res = Db::name('shop_goods_reviews')->insertAll($data);
		if($res <  count($data)){
			Db::rollback();
			return res_json(-1);
		}

		$update = Db::name('shop_order')->where(['id' => $post['order_id']])->update(['order_status' => 32]);
		if(!$update){
			Db::rollback();
			return res_json(-2);
		}

		Db::commit();
		return res_json(1);
	}

	/**
	* 个人中心收藏
	*/
	public function collect()
	{
		$words = trim($this->request->get('wd'));

		View::assign('words', $words);
		return View::fetch();
	}

	public function collectList()
	{
		$page = $this->request->post('pageStart') ?: 1;
		$limit = $this->request->post('perpage') ?: 6;
		$words = $this->request->post('words');

		$where = [
			['c.user_id', '=', $this->userId],
			['g.status', '=', 1],
			['g.is_sold', '=', 1],
			['s.status', '=', 1],
			['s.is_sold', '=', 1]
		];

		if($words){
			$where[] =  ['g.goods_name', 'LIKE', '%'.$words.'%'];
		}

		$list = Db::name('user_goods_collection')->alias('c')->field('c.id,g.id goods_id,g.goods_name,min(s.price) price,s.sku_img')->join('shop_goods g', 'c.goods_id=g.id')->join('shop_goods_sku s', 'g.id=s.goods_id')->where($where)->group('g.id')->page($page, $limit)->select();
		empty($list) && exit(res_json_native(-1));

		return res_json(1, $list);
	}

	public function cancelCollcet()
	{
		$id = $this->request->post('id');
		empty($id) && exit(res_json_native(-1));

		$res = Db::name('user_goods_collection')->where(['id' => $id])->delete();
		!$res && exit(res_json_native(-2));

		return res_json(1);
	}

	/**
	* 收货地址
	*/
	public function address()
	{
		$address = Db::name('shop_receiver_address')->where(['user_id' => $this->userId])->order(['is_default' => 'desc', 'id' => 'desc'])->select();

		if($preorder = $this->request->param('preorder')){
			View::assign('preorder', $preorder);
		}

		View::assign('address', $address);
		return View::fetch();
	}

	public function addressAdd()
	{
		return View::fetch();
	}

	public function newAddress()
	{
		$post = $this->request->post();

		$area = explode('-', $post['area']);
		$data = [
			'name' => $post['userName'],
			'phone' => $post['userPhone'],
			'province' => $area[0],
			'city' => $area[1],
			'district' => $area[2],
			'address' => $post['address'],
			'user_id' => $this->userId,
			'is_default' => $post['isdefault']
		];

		Db::startTrans();

		if($post['isdefault'] == 1){
			Db::name('shop_receiver_address')->where(['user_id' => $this->userId, 'is_default' => 1])->update(['is_default' => 0]);
		}

		$res = Db::name('shop_receiver_address')->insert($data);
		if(!$res){
			Db::rollback();
			return res_json(-1, 'fail');
		}

		Db::commit();
		return res_json(1, 'success');
	}

	public function addressEdit(int $id)
	{
		$info = [];
		$id && $info = Db::name('shop_receiver_address')->where(['user_id' => $this->userId, 'id' => $id])->find();

		View::assign('info', $info);
		return View::fetch();
	}

	public function editAddress()
	{
		$post = $this->request->post();

		$area = explode('-', $post['area']);
		$data = [
			'name' => $post['userName'],
			'phone' => $post['userPhone'],
			'province' => $area[0],
			'city' => $area[1],
			'district' => $area[2],
			'address' => $post['address'],
			'user_id' => $this->userId,
			'is_default' => $post['isdefault']
		];

		Db::startTrans();

		if($post['isdefault'] == 1){
			Db::name('shop_receiver_address')->where(['user_id' => $this->userId, 'is_default' => 1])->update(['is_default' => 0]);
		}

		$res = Db::name('shop_receiver_address')->where(['id' => $post['id']])->update($data);
		
		if(!is_numeric($res)){
			Db::rollback();
			return res_json(-1, 'fail');
		}

		Db::commit();
		return res_json(1, 'success');
	}
}