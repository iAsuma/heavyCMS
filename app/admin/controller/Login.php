<?php
namespace app\admin\controller;

use think\Request;
use app\admin\facade\Register;
use util\Hook;

class Login 
{
    public function index(Request $request)
    {
        if(app('register')->isLogined()){
            //如果登录不再重复登录
            return redirect('/admin');
        }
        return view('theme1');
    }

    public function checkLogin(Request $request)
    {
        $loginUser = Register::check($request->post());
        ($loginUser && !Register::login($loginUser, $request->post('remembered'))) && exit(res_json_str(0, '登录失败'));;
        
        Hook::listen('admin_log', ['登录', '登录页登录系统']); //监听登录行为

        return res_json(1);
    }
    
    public function logout()
    {
        Register::logout();
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