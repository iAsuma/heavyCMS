<?php
namespace app\shop\controller;
use think\Db;
/**
 * 商城
 * @author asuma(lishuaiqiu)
 */
class Index extends Base
{
	
	public function index()
	{
		$banners = $this->getBanners();
		$flashSale = $this->flashSale();
		$reco = $this->recommended();
		
		$this->assign('banner', $banners);
		$this->assign('flashSale', $flashSale);
		$this->assign('reco', $reco);
		return $this->fetch();
	}

	/*轮播图*/
	public function getBanners()
	{
		$banner = Db::table('shop_banner')->field('img,landing_url')->order(['sorted', 'id'=>'desc'])->limit(5)->select();

		return $banner;
	}

	/*推荐位*/
	public function recommended()
	{
		$info = Db::table('shop_reco_goods')->alias('a')->field('c.*,d.name rec_name')->where("(SELECT count(*) FROM shop_reco_goods WHERE a.rec_id=rec_id AND a.create_time<create_time) < 6")->leftjoin('(SELECT g.id goods_id,g.goods_name,min(s.price) price,s.market_price,s.sku_img FROM shop_goods g LEFT JOIN shop_goods_sku s ON g.id=s.goods_id WHERE g.status =1 AND g.is_sold=1 AND s.status = 1 AND s.is_sold =1 GROUP BY g.id) c', 'a.goods_id=c.goods_id')->leftjoin('shop_reco_place d', 'd.id=a.rec_id')->where('c.goods_id', 'NOT NULL')->where('d.name', '<>', '限时抢购')->order(['d.sorted' ,'a.rec_id', 'a.create_time' => 'desc'])->select();

		$rec = [];

		foreach ($info as $v) {
			$rec[$v['rec_name']][] = $v;
		}
		
		return $rec;
	}

	/*限时抢购*/
	public function flashSale()
	{
		$info = Db::table('shop_reco_goods')->field('c.*,b.name rec_name')->alias('a')->leftjoin('shop_reco_place b', 'b.id=a.rec_id')->leftjoin('(SELECT g.id goods_id,g.goods_name,min(s.price) price,s.market_price,s.sku_img FROM shop_goods g LEFT JOIN shop_goods_sku s ON g.id=s.goods_id WHERE g.status =1 AND g.is_sold=1 AND s.status = 1 AND s.is_sold =1 GROUP BY g.id) c', 'a.goods_id=c.goods_id')->where('b.name', '=', '限时抢购')->order('a.create_time', 'desc')->limit(3)->select();

		return $info;
	}

	/*分类页*/
	public function classify()
	{
		$levelOne = Db::table('shop_classification')->where(['pid' => 0])->select();

		$this->assign('from', $this->request->get('from') ?? '');
		$this->assign('levelOne', $levelOne);
		return $this->fetch();
	}

	public function getSecondClass()
	{
		$banner = $this->getAnyBanner();

		$pid = (int)$this->request->post('id');

		$classify = Db::table('shop_classification')->where(['pid' => $pid])->select();

		return res_json(1, [$banner, $classify]);
	}

	public function getAnyBanner()
	{
		$banner = $this->getBanners();
		if(empty($banner)){
			return '';
		}
		
		$rand = rand(0, count($banner)-1);
		return $banner[$rand];
	}

	/*搜索*/
	public function search()
	{
		$pid = (int)$this->request->get('pid');
		$words = trim($this->request->get('wd'));
		$type = $this->request->get('t') ?: 'norm';

		if($pid){
			$info = Db::table('shop_classification')->field('id,name')->where(['id' => $pid])->find();
		}else{
			$wd = $words ?: '全部';
			$info = ['name' => '搜索-'.$wd, 'id' => ''];
		}

		$this->assign('words', $words);
		$this->assign('info', $info);
		$this->assign('t', $type);
		return $this->fetch();
	}

	public function searchList()
	{
		$page = $this->request->post('pageStart') ?: 1;
		$limit = $this->request->post('perpage') ?: 6;
		$pid = $this->request->post('pid');
		$words = $this->request->post('words');
		$type = $this->request->post('type');

		$where = [
			['g.status', '=', 1],
			['g.is_sold', '=', 1],
			['s.status', '=', 1],
			['s.is_sold', '=', 1]
		];

		if($pid){
			$where[] = ['g.classification_id', '=', $pid];
		}

		if($words){
			$where[] =  ['g.goods_name', 'LIKE', '%'.$words.'%'];
		}

		$query = Db::table('shop_goods')->alias('g')->field('g.id goods_id,g.goods_name,min(s.price) price,s.sku_img,CASE WHEN o.buy IS NULL THEN 0 ELSE o.buy END buy')->join('shop_goods_sku s', 'g.id=s.goods_id')->leftjoin('(SELECT a.goods_id,count(a.goods_id) AS buy FROM shop_order_detail a LEFT JOIN shop_order b ON a.order_no=b.order_no WHERE b.order_status IN (1,2,3,5,6,11,31,32) GROUP BY goods_id) o', 'g.id=o.goods_id')->where($where)->group('g.id');

		if($type == 'sell'){
			$query->order('buy', 'desc');
		}else if($type == 'price'){
			$query->order('price', 'asc');
		}else if($type == 'priced'){
			$query->order('price', 'desc');
		}else{
			$query->order('goods_id', 'desc');
		}

		$list = $query->page($page, $limit)->select();
		empty($list) && exit(res_json_native(-1));

		return res_json(1, $list);
	}
}