<?php
/**
 * @author lishuaiqiu 2019-11-06
 * @param  string $unique_id 唯一ID
 * @return string 生成订单号 规则：年[0]+时分+年[1]+月日+唯一ID+四位随机数
 */
function make_order_no($unique_id='')
{
    $y = date('y');
    $d = date('md');
    $t = date('Hi');

    $unique_id = str_pad($unique_id, 4, 0, STR_PAD_LEFT);
    $rand4 = rand(1000,9999);

    return $y[0].$t.$y[1].$d.$unique_id.$rand4;
}