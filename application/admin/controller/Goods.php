<?php
namespace app\admin\controller;
use think\Db;
/**
 * 
 */
class Goods extends Base
{
	
	public function create()
	{

		$classifyArr = Db::table('shop_classification')->field('id,name,pid')->select();

		$tree = new \util\Tree($classifyArr);
		$classify = $tree->leaf();
		
		$this->assign('classify', $classify);
		return $this->fetch();
	}
}