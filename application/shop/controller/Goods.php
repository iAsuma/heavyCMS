<?php
namespace app\shop\controller;
use think\Controller;
use think\Request;
/**
 * 
 */
class Goods extends Controller
{
	
	public function detail(Request $request)
	{
		$id = (int)$request->param('id');
		// dump($id);

		return $this->fetch();
	}
}