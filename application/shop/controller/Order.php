<?php
namespace app\shop\controller;
/**
 * 订单相关
 */
class Order extends Base
{
	/*
	* 订单预览
	*/
	public function preSure()
	{
		return $this->fetch();
	}
}