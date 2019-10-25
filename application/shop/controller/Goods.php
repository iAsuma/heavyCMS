<?php
namespace app\shop\controller;
use think\Request;
/**
 * 
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