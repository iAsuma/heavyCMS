<?php
namespace app\shop\controller;
use think\Request;
use think\Db;
/**
 * 商品
 * @author asuma(lishuaiqiu)
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

	public function getAttrInfo(Request $request)
	{
		$goods_id = $request->post('goods_id');
		$attrs = $request->post('attrs');
		
		$query = Db::table('shop_goods_sku')->field('id,price,stocks,sku_img')->where('goods_id', '=', $goods_id);

		foreach ($attrs as $v) {
			$query->where('sku', 'LIKE', '%"'.$v.'"%');
		}

		$sku = $query->find();

		return res_json(1, $sku);
	}

	public function getGoodsReviews(Request $request)
	{
		$page = $request->post('pageStart') ?: 1;
		$limit = $request->post('perpage') ?: 5;

		$goods_id = $request->post('goods_id');

		$table = Db::table('shop_goods_reviews')->field('goods_id,content,imgs,stars,user_id,create_time')->group('goods_id,user_id')->buildSql();
		$list = Db::table($table)->alias('r')->field('u.nickname,u.headimgurl,r.*')->join('users u', 'r.user_id=u.id')->page($page, $limit)->where(['goods_id' => $goods_id])->select();
		foreach ($list as &$v) {
			$v['imgs'] = explode(',', $v['imgs']);
			$v['create_time'] = date('Y-m-d', strtotime($v['create_time']));
		}
		empty($list) && exit(res_json_native(-1));

		return res_json(1, $list);
	}
}