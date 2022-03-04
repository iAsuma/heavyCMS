<?php
namespace app\admin\controller;

use think\facade\View;
use think\Request;
use think\facade\Db;
use think\facade\Hook;
use wechat\facade\Loader as WeChat;

/**
* 订单管理
* @author zhaoyun  
* lishuaiqiu  2019-11-13 审阅
*/
class Order extends Base
{

    /**
     * 订单列表
     */
    public function index()
    {
        return View::fetch();
    }

    /**
     * 订单列表
     * lishuaiqiu 修改于2019-11-13
     */
    public function orderList()
    {   
        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
            ['o.status', '<>', -1],
            ['o.order_no', '=', $get['order_no'] ?? ''],
            ['o.pay_type', '=', $get['pay_type'] ?? '']
        ];

        if($get['order_status'] ?? ''){
            if($get['order_status'] == 3){
                $where[] = ['o.order_status', 'IN', '3,32'];
            }else if($get['order_status'] == 'dai'){
                $where[] = ['o.order_status', '=', 0];
            }else{
                $where[] = ['o.order_status', '=', $get['order_status'] ?? ''];
            }
        }else{
            $where[] = ['o.order_status', 'IN', '0,1,2,3,4,32'];
        }
        
        $formWhere = $this->parseWhere($where);

        $countQuery = Db::table('shop_order')->alias('o')->where($formWhere);
        $query = Db::table('shop_order')->alias('o')->leftJoin('users u','u.id = o.user_id')->field('o.id,o.order_no,o.price,FROM_UNIXTIME(o.pay_time, "%Y-%m-%d %H:%i:%s") pay_time,o.pay_money,o.pay_type,o.receiver_name,o.receiver_phone,o.order_status,u.nickname,FROM_UNIXTIME(o.create_time, "%Y-%m-%d %H:%i:%s") AS create_time')->where($formWhere)->page($page, $limit);

        if($get['order_status'] == 1){
            $query->order('o.pay_time', 'DESC');
        }else{
            $query->order('o.id', 'desc');
        }

        $count = $countQuery->count();
        $data = $query->select();
 
        return table_json($data, $count);
    }

    /**
     * 订单详情
     * 代码重构 @lishuaiqiu 2019-11-14
     */
    public function detail()
    {
        $order_no = $this->request->get('order_no');
        if(empty($order_no)){
            return '';
        }

        //订单基本信息
        $order = Db::table('shop_order')->alias('o')->field('o.*,u.nickname')->leftJoin('users u', 'o.user_id=u.id')->where(['o.order_no' => $order_no, 'o.status' => 1])->find();
        View::assign('order', $order);

        //商品信息
        $orderDetail = Db::table('shop_order_detail')->field('id,goods_name,unit_price,goods_num,goods_sku')->where(['order_no' => $order_no])->select();
        View::assign('detail', $orderDetail);

        return View::fetch();
    }

    public function delivery()
    {
        return View::fetch();
    }

    /**
     * 订单发货
     * @auhtor lishuaiqiu 2019-11-14
     */
    public function makeDelivery(Request $request)
    {
        $order_no = $request->post('order_no');
        !$order_no && exit(res_json_native(-1));
        
        $data = [
            'order_status' => 2,
            'express_company' => $request->post('express_company'),
            'express_code' => $request->post('express_code'),
            'delivery_time' => time()
        ];
        $res = Db::table('shop_order')->where(['order_no' => $order_no, 'status' => 1])->update($data);
        !$res && exit(res_json_native(-1));

        return res_json(1);
    }

    /**
     * 退货订单管理
     */
    public function returnOrder()
    {
        return View::fetch();
    }

    /**
     * 退货订单列表
     * lishuaiqiu 2019-11-14 代码重构
     */
    public function returnOrderList()
    {   
        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
             ['o.status', '<>', '-1']
            ,['o.order_status', 'IN', '5,6,11,31']
            ,['o.order_no', '=', $get['goods_name'] ?? '']
            ,['o.order_status', '=', $get['order_status'] ?? '']
        ];

        $fromWhere = $this->parseWhere($where);

        $count = Db::table('shop_order_return')->alias('r')->join('shop_order o', 'r.order_no=o.order_no')->where($fromWhere)->count();

        $list = Db::table('shop_order_return')->alias('r')->field('r.return_order_no,FROM_UNIXTIME(r.create_time, "%Y-%m-%d %H:%i:%s") AS create_time,o.id,o.order_no,o.price,o.pay_money,o.pay_type,o.order_status,u.nickname,FROM_UNIXTIME(o.pay_time, "%Y-%m-%d %H:%i:%s") AS pay_time')->join('shop_order o', 'r.order_no=o.order_no')->leftJoin('users u','u.id = r.user_id')->where($fromWhere)->page($page, $limit)->order('r.id', 'desc')->select();
 
        return table_json($list, $count);
    }

    /**
     * 确定退款
     * lishuaiqiu 2019-11-14 代码重构
     */
    public function backMoney(Request $request)
    {
        $order_no = $request->post('order_no');
        $data['order_status'] = 6;

        Db::startTrans();
        try {
            $up = Db::table('shop_order_return')->where('order_no', '=', $order_no) -> update(['status' => 2, 'audit_time' => time()]);
            if(!$up){
                return res_json(-1, '审核状态更新失败1');
            }

            $res = Db::table('shop_order')->where('order_no', '=', $order_no) -> update(['order_status' => 5]);
            if(!$res){
                Db::rollback();
                return res_json(-2, '审核状态更新失败2');
            }

            // 发起微信退款            
            $refund = Db::table('shop_order_return')->where('order_no', '=', $order_no)->find();
            $result = WeChat::refundByOrderNo($order_no, $refund['return_order_no'], $refund['refund_fee'], $refund['refund_fee']);
            if(!$result[0]){
                Db::rollback();
                return res_json(-4, '微信退款失败');
            }

            $re1 = Db::table('shop_order')->where('order_no', '=', $order_no)->update(['order_status' => 6]);
            $re2 = Db::table('shop_order_return')->where('order_no', '=', $order_no)->update(['status' => 1, 'complete_time' => time()]);
            if(!$re1 || !$re2){
                Db::rollback();
                return res_json(-5, '退款状态修改失败');
            }

            Hook::listen('admin_log', ['退货订单管理：'. $order_no, '审核退款']);
            Db::commit();
            return res_json(1);

        } catch (\Exception $e) {
            Db::rollback();
            return res_json(-3, $e->getMessage());
        }
    }

    //订单追踪
    public function track()
    {   
        $id = $this->request->get('id');
        $info = Db::table('shop_order')->field('order_no,order_status,create_time,pay_time,delivery_time,complete_time')->where(['id' => $id])->find();

        if(in_array($info['order_status'], [5,6,11,31])){
            $ret = Db::table('shop_order_return')->where(['order_no' => $info['order_no']])->find();
            View::assign('ret', $ret);
        }

        View::assign('info', $info);
        return View::fetch();
    }

    //订单评价
    public function reviews()
    {
        $id = $this->request->get('id');
        $info = Db::table('shop_goods_reviews')->field('content,id,stars,order_id')->where(['order_id' => $id])->find();

        $isRe = Db::table('shop_goods_reviews')->where(['user_id' => 0, 'order_id' => $id])->find();
        if($isRe){
            View::assign('hasRe', $isRe);
        }else{
            View::assign('hasRe', false);
        }
        View::assign('info', $info);
        View::assign('order_no', $this->request->get('order_no'));
        return View::fetch();
    }

    /**
     * 订单评价回复
     * lishuaiqiu 2019-11-14 代码重构
     */
    public function recomment(Request $request)
    {
        if(checkFormToken($request->post())){
             $validate = \think\Validate::make([
                'recomment' => 'require|min:2',
            ],[
                'recomment.require'=> '请填写回复内容',
                'recomment.min'    => '分类名称最少不能少于2个字符'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

            $data = [];

            $orderGoods = Db::table('shop_order_detail')->field('goods_id,goods_sku_id')->where(['order_no' => $request->post('order_no')])->select();
            
            foreach ($orderGoods as $v) {
                $data[] = [
                    'content' => $request->post('recomment'),
                    'user_id' => 0,
                    'goods_id' => $v['goods_id'],
                    'goods_sku_id' => $v['goods_sku_id'],
                    'order_id' => $request->post('order_id'),
                    'create_time' => date('Y-m-d H:i:s')
                ];
            }

            Db::startTrans();
            try {

                $result = Db::table('shop_goods_reviews') -> insertAll($data);

                if(!$result || $result != count($data)){
                    Db::rollback();
                    return res_json(-1);
                }

                Hook::listen('admin_log', ['订单管理', '订单回复']);

                Db::commit();
                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                Db::rollback();
                return res_json(-100, $e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

}