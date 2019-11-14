<?php
namespace wechat;
use EasyWeChat\Factory as WeChatFactory;
use think\Db;
/**
 * EasyWeChat 微信SDK加载器
 */
class Loader 
{
	private $appConfig = [];
	private $config = [];

    public function __construct() {
        $wxConfig = Db::name('application_config')->order('id', 'desc')->cache('wx_config', 0, 'developer')->find();

        $this->config = [
            'app_id' => $wxConfig['app_id'],
            'secret' => $wxConfig['app_secret']
        ];

        $this->appConfig = $wxConfig;
        return $this->appConfig;
    }

    /**
	 * 微信公众号SDK入口
	 */
    public function officialAccount($config=[])
    {
    	$config = array_merge($this->config, $config);
    	$app = WeChatFactory::officialAccount($config);

    	return $app;
    }

	/**
	 * 微信支付SDK获取
	 */
	public function payment($config = [])
	{	
		$initConfig = [
			'mch_id' => $this->appConfig['mch_id'],
			'key' => $this->appConfig['partnerkey']
		];

		$config = array_merge($this->config, $initConfig, $config);
		$app = WeChatFactory::payment($config);
		return $app;
	}

	/**
	 * 微信公众号JsSdk配置获取
	 */
    public function jsConfig($APIs = [], $debug = false, $beta = false, $json = true)
	{
		$app = WeChatFactory::officialAccount($this->config);
		$config = $app->jssdk->buildConfig($APIs, $debug, $beta, $json);
		
		return $config;
	}

	/**
	 * 微信支付统一下单
	 */
	public function unifyMakeOrder($trade, $isContract = false)
	{
		$app = $this->payment();

		$param = [
		    'body' => $this->appConfig['app_name'].'在线支付',
		    'out_trade_no' => '',
		    'total_fee' => 100,
		    'notify_url' => '', //支付结果通知网址
		    'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
		    'openid' => '',
		];
		
		$param = array_merge($param, $trade);
		$result = $app->order->unify($param, $isContract);

		if($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK' && $result['result_code'] == 'SUCCESS'){
			return [1, $result];
		}

		return [0, $result];
	}

	/**
	 * 根据自有订单号退款
	 */
	public function refundByOrderNo(string $order_no, string $refundNumber, float $totalFee, float $refundFee, array $config = [])
	{
		$config = [
                'cert_path' => $this->appConfig['cert_path'],
                'key_path' => $this->appConfig['key_path']
            ];
        $app = $this->payment($config);
        
        $result = $app->refund->byOutTradeNumber($order_no, $refundNumber, $totalFee*100, $refundFee*100);

        if(($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK' && $result['result_code'] == 'SUCCESS') || ($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK' && $result['err_code_des'] == '订单已全额退款')){
			return [1, $result];
		}
		
		return [0, $result];
	}
}