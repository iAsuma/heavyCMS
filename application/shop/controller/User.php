<?php
namespace app\shop\controller;
use think\Db;
/**
 * 个人中心(我的)
 */
class User extends Base
{

	public function index()
	{
		$report = $this->orderCount();

		$this->assign('report', $report);
		return $this->fetch();
	}

	public function orderCount()
	{
		$uid = $this->userId;
		$where = [
			'user_id' => $this->userId,
			'status' => 1
		];

		$data['nopay'] = Db::table('shop_order')->where($where)->where(['order_status' => 0])->count();
		$data['nosend'] = Db::table('shop_order')->where($where)->where(['order_status' => 1])->count();
		$data['noget'] = Db::table('shop_order')->where($where)->where(['order_status' => 2])->count();
		
		return $data;
	}

	public function collect()
	{
		$words = trim($this->request->get('wd'));

		$this->assign('words', $words);
		return $this->fetch();
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

		$list = Db::table('user_goods_collection')->alias('c')->field('g.id goods_id,g.goods_name,min(s.price) price,s.sku_img')->join('shop_goods g', 'c.goods_id=g.id')->join('shop_goods_sku s', 'g.id=s.goods_id')->where($where)->group('g.id')->page($page, $limit)->select();
		empty($list) && exit(res_json_native(-1));

		return res_json(1, $list);
	}

	public function address()
	{
		$address = Db::table('shop_receiver_address')->where(['user_id' => $this->userId])->order(['is_default' => 'desc', 'id' => 'desc'])->select();

		$this->assign('address', $address);
		return $this->fetch();
	}

	public function addressAdd()
	{
		return $this->fetch();
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
			Db::table('shop_receiver_address')->where(['user_id' => $this->userId, 'is_default' => 1])->update(['is_default' => 0]);
		}

		$res = Db::table('shop_receiver_address')->insert($data);
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
		$id && $info = Db::table('shop_receiver_address')->where(['user_id' => $this->userId, 'id' => $id])->find();

		$this->assign('info', $info);
		return $this->fetch();
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
			Db::table('shop_receiver_address')->where(['user_id' => $this->userId, 'is_default' => 1])->update(['is_default' => 0]);
		}

		$res = Db::table('shop_receiver_address')->where(['id' => $post['id']])->update($data);
		
		if(!is_numeric($res)){
			Db::rollback();
			return res_json(-1, 'fail');
		}

		Db::commit();
		return res_json(1, 'success');
	}

	public function order()
	{
		$type = $this->request->get('o');
		$type == '' && $type = 'all';

		$this->assign('type', $type);
		return $this->fetch();
	}
}