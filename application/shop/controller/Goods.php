<?php
namespace app\shop\controller;
use think\Request;
/**
 * 商品
 */
class Goods extends Base
{
	
	public function detail(Request $request)
	{
		$id = (int)$request->param('id');
		// dump($id);

		return $this->fetch();
	}
}