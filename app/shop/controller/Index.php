<?php
namespace app\shop\controller;
use think\facade\Db;
use think\facade\View;

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
		
		View::assign('banner', $banners);
		View::assign('flashSale', $flashSale);
		View::assign('reco', $reco);
		return View::fetch();
	}

	/*轮播图*/
	public function getBanners()
	{
		$banner = Db::name('shop_banner')->field('img,landing_url')->where(['status' => 1])->order(['sorted', 'id'=>'desc'])->limit(5)->select();

		return $banner;
	}

	/*推荐位*/
	public function recommended()
	{
		$goodsTable = Db::name('shop_goods')->alias('g')->field('g.id AS goods_id,g.goods_name,s.price,s.market_price,s.sku_img')->join('(SELECT sku1.*,sku2.market_price,sku2.stocks,sku2.sku_img FROM (SELECT goods_id,MIN(price) as price FROM shop_goods_sku WHERE status = 1 AND is_sold = 1 GROUP BY goods_id)sku1,shop_goods_sku sku2 WHERE sku1.price=sku2.price AND sku1.goods_id=sku2.goods_id) s', 'g.id=s.goods_id')->where([
			'g.status' => 1,
			'g.is_sold' => 1
		])->buildSql();

		$info = Db::name('shop_reco_goods')->alias('a')->field('c.*,d.name rec_name')->leftjoin('shop_reco_place d', 'd.id=a.rec_id')->leftjoin($goodsTable. ' c', 'a.goods_id=c.goods_id')->where("(SELECT count(*) FROM (select x.* from shop_reco_goods x left join shop_goods y on x.goods_id =y.id where y.status=1 and y.is_sold = 1) r WHERE a.rec_id=rec_id AND a.create_time<create_time) < 6")->where('c.goods_id', 'NOT NULL')->where('d.name', '<>', '限时抢购')->order(['d.sorted' ,'a.rec_id', 'a.create_time' => 'desc'])->select();

		$rec = [];

		foreach ($info as $v) {
			$rec[$v['rec_name']][] = $v;
		}
		
		return $rec;
	}

	/*限时抢购*/
	public function flashSale()
	{
		$goodsTable = Db::name('shop_goods')->alias('g')->field('g.id AS goods_id,g.goods_name,s.price,s.market_price,s.sku_img')->join('(SELECT sku1.*,sku2.market_price,sku2.stocks,sku2.sku_img FROM (SELECT goods_id,MIN(price) as price FROM shop_goods_sku WHERE status = 1 AND is_sold = 1 GROUP BY goods_id)sku1,shop_goods_sku sku2 WHERE sku1.price=sku2.price AND sku1.goods_id=sku2.goods_id) s', 'g.id=s.goods_id')->where([
			'g.status' => 1,
			'g.is_sold' => 1
		])->buildSql();

		$info = Db::name('shop_reco_goods')->field('c.*,b.name rec_name')->alias('a')->leftjoin('shop_reco_place b', 'b.id=a.rec_id')->leftjoin($goodsTable.' c', 'a.goods_id=c.goods_id')->where('b.name', '=', '限时抢购')->order('a.create_time', 'desc')->limit(3)->select();

		return $info;
	}

	/*分类页*/
	public function classify()
	{
		$levelOne = Db::name('shop_classification')->where(['pid' => 0])->select();

		View::assign('from', $this->request->get('from') ?? '');
		View::assign('levelOne', $levelOne);
		return View::fetch();
	}

	public function getSecondClass()
	{
		$banner = $this->getAnyBanner();

		$pid = (int)$this->request->post('id');

		$classify = Db::name('shop_classification')->where(['pid' => $pid])->select();

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

	public function classify2()
	{
		$levelOne = Db::name('shop_classification')->where(['pid' => 0])->select();
		
		View::assign('from', $this->request->get('from') ?? '');
		View::assign('levelOne', $levelOne);
		return View::fetch();
	}

	public function getSecondClass2()
	{
		$banner = $this->getAnyBanner();

		$pid = (int)$this->request->post('id');

		$query = 'SELECT a.*,b.pid FROM ( SELECT g.id,g.goods_name,g.classification_id,g.goods_imgs,c.name FROM shop_goods g LEFT JOIN shop_classification c ON g.classification_id=c.id WHERE g.status=1 AND g.is_sold = 1 ORDER BY g.id DESC) a LEFT JOIN shop_classification b ON a.classification_id=b.id WHERE (SELECT count(*) FROM (SELECT g.id,g.goods_name,g.classification_id,g.goods_imgs,c.name FROM shop_goods g LEFT JOIN shop_classification c ON g.classification_id=c.id WHERE g.status=1 AND g.is_sold = 1)x WHERE a.classification_id=classification_id AND a.id < id) < 6 AND b.pid='.$pid;

		$goods6 = Db::query($query);
		$list = [];
		
		foreach ($goods6 as &$v) {
			$imgs = explode(',', $v['goods_imgs']);
			$v['main_img'] = $imgs[0];
			$list[$v['name']][0] = $v['classification_id'];
			$list[$v['name']][1][] = $v;
		}

		return res_json(1, [$banner, $list]);
	}

	/*搜索*/
	public function search()
	{
		$pid = (int)$this->request->get('pid');
		$words = trim($this->request->get('wd'));
		$type = $this->request->get('t') ?: 'norm';

		if($pid){
			$info = Db::name('shop_classification')->field('id,name')->where(['id' => $pid])->find();
		}else{
			$wd = $words ?: '全部';
			$info = ['name' => '搜索-'.$wd, 'id' => ''];
		}

		View::assign('words', $words);
		View::assign('info', $info);
		View::assign('t', $type);
		return View::fetch();
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

		$query = Db::name('shop_goods')->alias('g')->field('g.id goods_id,g.goods_name,min(s.price) price,s.sku_img,CASE WHEN o.buy IS NULL THEN 0 ELSE o.buy END buy')->join('shop_goods_sku s', 'g.id=s.goods_id')->leftjoin('(SELECT a.goods_id,count(a.goods_id) AS buy FROM shop_order_detail a LEFT JOIN shop_order b ON a.order_no=b.order_no WHERE b.order_status IN (1,2,3,5,6,11,31,32) GROUP BY goods_id) o', 'g.id=o.goods_id')->where($where)->group('g.id');

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