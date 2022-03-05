<?php
namespace app\shop\controller;
use think\facade\View;
use think\Request;
use think\facade\Db;
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
			return View::fetch('index@public/tips', ['type' => 'little', 'code' => '商品不存在']);
		}

		$where = [
			'g.id' => $id,
			'g.status' => 1,
			'g.is_sold' => 1
		];

		$info = Db::name('shop_goods')->alias('g')->field('g.*,s.price,s.market_price,s.stocks')->join('(SELECT a.*,b.market_price,b.stocks FROM (SELECT goods_id,MIN(price) as price FROM shop_goods_sku WHERE status = 1 AND is_sold = 1 GROUP BY goods_id)a,shop_goods_sku b WHERE a.price=b.price AND a.goods_id=b.goods_id) s', 'g.id=s.goods_id')->where($where)->find();

		if(empty($info)){
			return View::fetch('index@public/tips', ['type' => 'little', 'code' => '商品不存在或已下架']);
		}

		$info['attrs'] = json_decode($info['goods_sku_attributes'], true);

		$info['main_img'] = explode(',', $info['goods_imgs']);
		$info['imgcount'] = count($info['main_img']);

		$time = strtotime(date('Y-m-01 00:00:00'));
		$info['sold'] = Db::name('shop_order_detail')->alias('a')->leftjoin('shop_order b', 'a.order_no=b.order_no')->where(['a.goods_id' => $id])->where('a.create_time', '>=', $time)->where('b.order_status', 'IN', '1,2,3,5,6,11,31,32')->count();

		$info['collectd'] = $this->isCollected($id);
		
		View::assign('info', $info);
		return View::fetch();
	}

	public function getAttrInfo(Request $request)
	{
		$goods_id = $request->post('goods_id');
		$attrs = $request->post('attrs');
		
		$query = Db::name('shop_goods_sku')->field('id,price,stocks,sku_img,is_sold')->where('goods_id', '=', $goods_id);

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

		$table = Db::name('shop_goods_reviews')->field('goods_id,content,imgs,stars,user_id,create_time,order_id')->group('user_id,content')->where('user_id', '<>', 0)->buildSql();

		$rightTable = Db::name('shop_goods_reviews')->field('content,goods_id,order_id')->where(['user_id' => 0])->buildSql();

		$list = Db::name($table)->alias('r')->field('u.nickname,u.headimgurl,r.*,r2.content AS content2')->leftjoin('users u', 'r.user_id=u.id')->page($page, $limit)->where(['r.goods_id' => $goods_id])->leftjoin($rightTable.' r2', 'r.goods_id=r2.goods_id AND r.order_id=r2.order_id')->order('r.create_time', 'desc')->select();
		
		foreach ($list as &$v) {
			$v['imgs'] = explode(',', $v['imgs']);
			$v['create_time'] = date('Y-m-d', strtotime($v['create_time']));
		}
		empty($list) && exit(res_json_native(-1));

		return res_json(1, $list);
	}
}