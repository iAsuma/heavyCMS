<?php
namespace app\index\controller;
use app\common\BaseController;
/**
 * 登录页
 */
class Login extends BaseController
{
	
	public function onlyWechat()
	{
		if($this->request->InApp == 'WeChat'){
		    return redirect('/shop');
		}
		
		return view('index@public/tips', ['type' => 'pride', 'code' => '请使用微信客户端打开']);
	}
}