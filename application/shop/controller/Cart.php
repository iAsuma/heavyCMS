<?php
namespace app\shop\controller;
/**
 * 购物车
 */
class Cart extends Base
{
	
	public function index()
	{
		return $this->fetch();
	}
}