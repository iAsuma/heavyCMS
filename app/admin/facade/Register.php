<?php
namespace app\admin\facade;
use think\Facade;
/**
 * 注册/登录门面
 */
class Register extends Facade
{
	
	protected static function getFacadeClass(): string
    {
    	return 'register';
    }
}