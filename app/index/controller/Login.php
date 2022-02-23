<?php
namespace app\index\controller;
use think\Controller;
/**
 * 登录页
 */
class Login extends Controller
{
	
	public function onlyWechat()
	{
		if($this->request->InApp == 'WeChat'){
			return $this->redirect('/shop');
		}
		
		return view('index@public/tips', ['type' => 'pride', 'code' => '请使用微信客户端打开']);
	}
}