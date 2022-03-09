<?php

namespace app\admin\model;

use think\Model;

class AdminUser extends Model
{
    /*
     * 获取基础管理员信息
     * */
    public function getInfo($username){
        $userInfo = $this->field('id,name,login_name,phone,email,password,head_img,status')
            ->where('login_name|email|phone', '=', $username)
            ->where('status' ,'<>', -1)
            ->findOrEmpty();

        return $userInfo;
    }

    /*
     * cookie安全登录用
     * */
    public function getForceInfo($id, $loginName, $password){
        $userInfo = $this->field('id,name,login_name,head_img')
            ->where('id', '=', $id)
            ->where('login_name', '=', $loginName)
            ->where('password','=', $password)
            ->findOrEmpty();

        return $userInfo;
    }
}