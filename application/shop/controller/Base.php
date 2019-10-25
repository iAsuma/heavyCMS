<?php
namespace app\shop\controller;
use think\Controller;
use think\facade\Session;

class Base extends Controller
{
	protected $userId = NULL;
	protected $userName = NULL;
	protected $userHeadImg = NULL;

	protected function initialize()
	{
		$this->userId = Session::get('wapUser.id');
		$this->userName = Session::get('wapUser.name');
		$this->userHeadImg = Session::get('wapUser.headimgurl');
	}

	/**
	* @param $code 错误信息
    * @param type 显示卡通的表情 error 哭；success 笑；little 委屈；pride 撇嘴；surprised 惊讶; none 不显示
    * 缺省页面 lishuaiqiu @2018-12-18
    */
	public function defaultTpl($code="404|页面飞走了！", $type="error")
	{
		return $this->fetch('public/tips', ['type' => $type, 'code' => $code]);
	}
}