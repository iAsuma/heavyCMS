<?php

namespace app\admin\controller;

use think\Request;
use Db;
use think\facade\Hook;
/**
* 订单管理
* @author zhaoyun  
*/
class Order extends Base
{

    /**
     * 订单列表
     * @author zhaoyun  
     */
    public function list()
    {
        return $this->fetch();
    }

    public function dataList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
             ['shop_order.status', '<>', '-1']
            ,['shop_order.order_no', '=', $get['order_no'] ?? '']
            ,['shop_order.order_status', '=', $get['order_status'] ?? '']
            ,['shop_order.pay_type', '=', $get['pay_type'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);

        $countQuery = Db::table('shop_order')->where($formWhere);
        $query = Db::table('shop_order')->alias('o')->leftJoin('users u','u.id = o.user_id')->field('o.id,o.order_no,o.price,o.pay_money,o.pay_type,o.receiver_name,o.receiver_phone,o.order_status,u.name,FROM_UNIXTIME(o.create_time, "%Y-%m-%d %h:%i:%s") AS create_time')->where($formWhere)->page($page, $limit)->order('o.id', 'desc');
        $count = $countQuery->count();
        $data = $query->select();
 
        return table_json($data, $count);
    }

    /**
     * 退货订单管理
     * @author zhaoyun  
     */
    public function back()
    {
        return $this->fetch();
    }

    public function detailList()
    {   

        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
             ['shop_order.status', '<>', '-1']
             ,['shop_order.order_status', '>', '4']
            ,['shop_order.order_no', '=', $get['goods_name'] ?? '']
            ,['shop_order.order_status', '=', $get['order_status'] ?? '']
            ,['shop_order.pay_type', '=', $get['pay_type'] ?? '']
        ];

        $formWhere = $this->parseWhere($where);

        $countQuery = Db::table('shop_order')->where($formWhere);
        $query = Db::table('shop_order')->alias('o')->leftJoin('users u','u.id = o.user_id')->field('o.id,o.order_no,o.price,o.pay_money,o.pay_type,o.receiver_name,o.receiver_phone,o.order_status,u.name,FROM_UNIXTIME(o.create_time, "%Y-%m-%d %h:%i:%s") AS create_time')->where($formWhere)->page($page, $limit)->order('o.id', 'desc');
        $count = $countQuery->count();
        $data = $query->select();
 
        return table_json($data, $count);
    }

    //订单详情
    public function detail()
    {
        $order_no = (int)$this->request->get('order_no');

        //商品信息
        $list = $this->goods_detail($order_no);
        $this->assign('list', $list);

        //合计
        $total = 0;
        foreach ($list as $k => $v) {
            $total += $v['unit_price']*$v['goods_num'];
        }
        $this->assign('total', $total);
        
        //订单详情
        $order_no && $info = Db::table('shop_order_detail')->field('order_no,user_id')->where(['order_no' => $order_no])->find();
        $this->assign('info', $info);

        //用户信息
        $user = Db::table('users')->field('name')->where(['id' => $info['user_id']])->find();
        $this->assign('user', $user);

        //订单基本信息
        $order_no && $order = Db::table('shop_order')->field('id,order_status,express_code,receiver_name,receiver_phone,receiver_address,pay_type,freight,price')->where(['order_no' => $order_no])->find();
        isset($info) && $this->assign('order', $order);

        //订单是否评价
        $order_no && $setcontent = Db::table('shop_goods_evaluate')->field('content,stars')->where(['order_id' => $order['id']])->find();
        $this->assign('setcontent', $setcontent);

        return $this->fetch();
    }

    private function goods_detail($order_no)
    {
        $order_no && $list = Db::table('shop_order_detail')->field('id,goods_name,unit_price,goods_num,goods_sku_id')->where(['order_no' => $order_no])->select();
        $str = '';
        foreach ($list as $k => $v) {
            $data = Db::table('shop_goods_sku')->field('sku')->where(['id' => $v['goods_sku_id']])->find();
            $arr = json_decode($data['sku'],true);
            foreach ($arr as $key => $val) {
                $str .= $val['title'].'：'.$val['attr'].'；';
            }
            
            $list[$k]['sku'] = $str;
            $str=''; 
        }
        return $list;
    }

    //确定退款
    public function backMoney(Request $request)
    {
        
        $id = $request->post('id');
        $data['order_status'] = 6 ;
    
        if (Db::table('shop_order') ->where('id', '=', $id) -> update($data)) { 
            Hook::listen('admin_log', ['订单管理', '已退款']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
      
    }

    //订单追踪
    public function track()
    {   
        $id = $this->request->get('id');
        $info = Db::table('shop_order')->field('order_status,create_time,pay_time,delivery_time,complete_time')->where(['id' => $id])->find();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }

    //订单评价
    public function assess()
    {
        $id = $this->request->get('id');
        $info = Db::table('shop_goods_evaluate')->field('content,id,stars')->where(['order_id' => $id])->find();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }

    //订单商家回复
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

            try {
                $data = [
                    'content' => $request->post('recomment'),
                    'user_id' => 0,
                    'goods_id' => 0,
                    'goods_sku_id' => 0,
                    'order_id' => $request->post('id')
                ];
                $result = Db::table('shop_goods_evaluate') -> insert($data);
                !$result && exit(res_json_native(-3, '回复失败'));
                Hook::listen('admin_log', ['订单管理', '订单回复']);

                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100,$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

}