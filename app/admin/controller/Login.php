<?php
namespace app\admin\controller;

use think\facade\Event;
use think\Request;
use app\admin\facade\Entrance;

class Login 
{
    public function index(Request $request)
    {
        if(Entrance::isLogin()){
            //如果登录不再重复登录
            return redirect('/admin');
        }
        return view('theme1');
    }

    public function checkLogin(Request $request)
    {
        $post = $request->post();
        $validate = new \app\admin\validate\Register;
        if(!$validate->scene('login')->check($post)){
            return rjson(500, $validate->getError());
        }

        $loginUser = Entrance::getAdminUserInfo($post['username']);

        $checkUser = Entrance::check($post, $loginUser);
        if($checkUser !== true) return rjson(500, $checkUser);
        $remembered = $post['remembered'] ?? '';
        if(!Entrance::login($loginUser, $remembered)) return rjson(0, '登录失败');

        Event::trigger('admin_log', ['登录', '登录页登录系统']); //监听登录行为
        return rjson(1);
    }
    
    public function logout()
    {
        Entrance::logout();
        return redirect('/admin/login');
    }

    public function register()
    {
        return view();
    }

    public function forget()
    {
        return view();
    }
}