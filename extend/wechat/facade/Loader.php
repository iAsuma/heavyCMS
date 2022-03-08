<?php
// +----------------------------------------------------------------------
// | QingCMS [ MAKE THINGS BETTER ]
// +----------------------------------------------------------------------
// | Author: asuma(lishuaiqiu) <weibo.com/770878450>
// +----------------------------------------------------------------------
namespace wechat\facade;
use think\Facade;

/**
 * EasyWechat加载门面类
 * @see \wechat\Loader
 * @method officialAccount(array $config = []) 微信公众号SDK入口
 * @method payment(array $config = [])  微信支付SDK获取
 * @method jsConfig($APIs = [], $debug = false, $beta = false, $json = true) 微信公众号JsSdk配置获取
 * @method unifyMakeOrder($trade, $isContract = false) 微信支付统一下单
 * @method refundByOrderNo(string $order_no, string $refundNumber, float $totalFee, float $refundFee, array $config = []) 根据自有订单号退款
 */
class Loader extends Facade
{
	
	protected static function getFacadeClass()
    {
    	return 'wechat\Loader';
    }
}
