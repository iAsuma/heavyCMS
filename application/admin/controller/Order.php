<?php

namespace app\admin\controller;

use think\Request;
use Db;
use think\facade\Hook;
/**
* 用户管理
* @author zhaoyun  
*/
class Order extends Base
{

    /**
     * 用户列表
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
            ,['shop_order.order_no', 'LIKE', $get['goods_name'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);

        $countQuery = Db::table('shop_order')->where($formWhere);
        $query = Db::table('shop_order')->alias('o')->leftJoin('users u','u.id = o.user_id')->field('o.id,o.order_no,o.price,o.pay_money,o.pay_type,o.receiver_name,o.receiver_phone,o.order_status,u.name,FROM_UNIXTIME(o.create_time, "%Y-%m-%d %h:%i:%s") AS create_time')->where($formWhere)->page($page, $limit)->order('o.id', 'desc');
        $count = $countQuery->count();
        $data = $query->select();
 
        return table_json($data, $count);
    }

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
            ,['shop_order.order_no', 'LIKE', $get['goods_name'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);

        $countQuery = Db::table('shop_order')->where($formWhere);
        $query = Db::table('shop_order')->alias('o')->leftJoin('users u','u.id = o.user_id')->field('o.id,o.order_no,o.price,o.pay_money,o.pay_type,o.receiver_name,o.receiver_phone,o.order_status,u.name,FROM_UNIXTIME(o.create_time, "%Y-%m-%d %h:%i:%s") AS create_time')->where($formWhere)->page($page, $limit)->order('o.id', 'desc');
        $count = $countQuery->count();
        $data = $query->select();
 
        return table_json($data, $count);
    }

    public function detail()
    {
        $order_no = (int)$this->request->get('order_no');
        $order_no && $list = Db::table('shop_order_detail')->where(['order_no' => $order_no])->select();
        isset($list) && $this->assign('list', $list);
        $total = 0;
        foreach ($list as $k => $v) {
            $total += $v['unit_price']*$v['goods_num'];
        }
        $this->assign('total', $total);
        
        $order_no && $info = Db::table('shop_order_detail')->where(['order_no' => $order_no])->find();
        isset($info) && $this->assign('info', $info);

        $user = Db::table('users')->field('name')->where(['id' => $info['user_id']])->find();
        isset($user) && $this->assign('user', $user);

        $order_no && $order = Db::table('shop_order')->where(['order_no' => $order_no])->find();
        isset($info) && $this->assign('order', $order);
        
        return $this->fetch();
    }

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

}