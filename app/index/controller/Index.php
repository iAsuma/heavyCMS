<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
//        if(!request()->isMobile()){
//            return redirect('/admin');
//        }
    	return "<h3>nothing here</h3>";
    }
    
    /**
    * 跳回上一个页面
    */
    public function redirectLast()
    {
        session('from_redirect', true);
        return redirect()->restore();
    }

    /**
    * 默认页渲染模板
    */
    public function defaultIndex()
    {
        return view('public/tips', ['type' => 'success', 'code' => '登录成功，请退出本页面']);
    }
}