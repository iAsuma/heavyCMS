<?php
namespace app\shop\controller;
use think\Db;
use wechat\facade\Loader as WeChat;
/**
 * 支付接口相关
 * @author asuma(lishuaiqiu)
 */
class Api
{	
	/*
	* 微信支付结果通知
	*/
	public function wxPayNotice()
	{
		$app = WeChat::payment();
		$response = $app->handlePaidNotify(function ($message, $fail) {
			$where = [
				['order_no', '=', $message['out_trade_no']],
				['status', '=', 1],
				['order_status', '=', 0]
			];
			$orderInfo = Db::table('shop_order')->field('id,order_no,price')->where($where)->find();
			if(!$orderInfo){
				return true; //查询不到订单不再通知
			}

			if ($message['return_code'] === 'SUCCESS') {
		        // 用户是否支付成功
		        if (isset($message['result_code']) && $message['result_code'] === 'SUCCESS') {
		        	//1.更新订单状态
		            $data = [
		            	'pay_money' => $message['cash_fee'] / 100,
		            	'pay_type' => 1,
		            	'pay_time' => time(),
		            	'third_trade_no' => $message['transaction_id'],
		            	'order_status' => 1
		            ];
		            $res = Db::table('shop_order')->where($where)->update($data);
		            !$res && i_log('订单支付状态更新失败');

		            //2.支付成功扣减库存
		            $detail = Db::table('shop_order_detail')->field('order_no,goods_num,goods_sku_id')->where('order_no', '=', $message['out_trade_no'])->select();
		            foreach ($detail as $v) {
		            	$change = Db::table('shop_goods_sku')->where('id', '=', $v['goods_sku_id'])->update(['stocks' => Db::raw('stocks-'.$v['goods_num'])]);
		            	!$change && i_log('商品sku：'.$v['goods_sku_id'].'库存扣减失败');

		            	$sku = Db::table('shop_goods_sku')->field('id,stocks')->where('id', '=', $v['goods_sku_id'])->find();
		            	if($sku['stocks'] < 1){
		            		Db::table('shop_goods_sku')->where('id', '=', $v['goods_sku_id'])->update(['is_sold' => 0]);
		            	}
		            }

		            return true;
		        // 用户支付失败
		        } elseif (isset($message['result_code']) && $message['result_code'] === 'FAIL') {
		            i_log('用户支付失败：'.json_encode($message));
		        }
		    } else {
		        return $fail('通信失败，请稍后再通知我');
		    }
		});

		$response->send();
	}
}