<?php
namespace app\shop\controller;
use think\Controller;
use think\Db;
/**
 * 
 */
class Index extends Controller
{
	
	public function index()
	{
		$banners = $this->getBanners();
		$reco = $this->recommended();
		
		$this->assign('banner', $banners);
		$this->assign('reco', $reco);
		return $this->fetch();
	}

	public function recommended()
	{
		$info = Db::table('shop_reco_goods')->alias('a')->field('c.*,d.name rec_name')->where("(SELECT count(*) FROM shop_reco_goods WHERE a.rec_id=rec_id AND a.create_time<create_time) < 5")->order('create_time', 'desc')->leftjoin('(SELECT g.id goods_id,g.goods_name,min(s.price) price,s.market_price,s.sku_img FROM shop_goods g LEFT JOIN shop_goods_sku s ON g.id=s.goods_id WHERE g.status =1 AND g.is_sold=1 AND s.status = 1 AND s.is_sold =1 GROUP BY g.id) c', 'a.goods_id=c.goods_id')->leftjoin('shop_reco_place d', 'd.id=a.rec_id')->where('c.goods_id', 'NOT NULL')->select();

		$rec = [];

		foreach ($info as $v) {
			$rec[$v['rec_name']][] = $v;
		}

		return $rec;
	}

	public function getBanners()
	{
		$banner = Db::table('shop_banner')->field('img,landing_url')->order('id', 'desc')->limit(5)->select();

		return $banner;
	}
}