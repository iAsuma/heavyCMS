<?php
namespace app\index\controller;
use app\common\BaseController;
use think\facade\View;

class Base extends BaseController
{
	protected function initialize()
	{

	}

	/**
	* @param string|int $code 错误信息
    * @param string type 显示卡通的表情 error 哭；success 笑；little 委屈；pride 撇嘴；surprised 惊讶; none 不显示
    * 缺省页面 lishuaiqiu @2018-12-18
    */
	public function defaultTpl($code="404|页面飞走了！", $type="error")
	{
		return View::fetch('public/tips', ['type' => $type, 'code' => $code]);
	}
}