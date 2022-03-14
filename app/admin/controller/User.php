<?php

namespace app\admin\controller;

use think\facade\View;
use think\Request;
use think\facade\Db;
use util\Hook;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Worksheet_Drawing;
/**
* 用户管理
* @author zhaoyun  
*/
class User extends Base
{

    /**
     * 用户列表
     * @author zhaoyun  
     */
    public function index()
    {
        return View::fetch();
    }

    public function userList()
    {
        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
             ['status', '<>', '-1']
            ,['name', 'LIKE', $get['name'] ?? '']
            ,['phone', 'LIKE', $get['phone'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);

        $countQuery = Db::name('users')->alias('u')->where($formWhere);
        $userQuery = Db::name('users')->field('id,nickname,name,phone,gender,status,create_time,country,province,city')->where($formWhere)->page($page, $limit)->order('id', 'asc');

        $count = $countQuery->count();
        $user = $userQuery->select();
 
        return table_json($user, $count);
    }

    public function userEdit()
    {
        $id = (int)$this->request->get('id');
        $id && $info = Db::name('users')->where(['id' => $id])->find();
        isset($info) && View::assign('info', $info);
        
        return View::fetch();
    }

    public function editUser(Request $request)
    {
        if(checkFormToken($request->post())){
            $validate = \util\Validate::make([
                'name' => 'require|min:2',
                'phone' => 'require|mobile',
                'gender' => 'require'
            ],[
                'name.require'=> '请填写姓名',
                'name.min'    => '姓名不能少于2个字符',
                'phone.require'    => '手机号必填',
                'phone.mobile'    => '手机号无效',
                'gender.require'    => '性别必填'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }

            $post = $request->post();

            Db::startTrans();
            try {
                
                $data = [
                        'name' => $request->post('name'),
                        'phone' => trim($request->post('phone')),
                        'gender' => $request->post('gender')
                ];

                $update = Db::name('users') ->where('id', (int)$post['id']) -> update($data);
                 
                if(!is_numeric($update)){
                    Db::rollback();
                    return res_json(-4, '修改失败');
                }
                Hook::listen('admin_log', ['用户管理', '修改用户信息：'.$data['name']]);
                Db::commit();
                destroyFormToken($post);
                return res_json(1);
            } catch (\Exception $e) {
                Db::rollback();
                return res_json(-100, $e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }


    public function del(Request $request)
    {
        $id = $request->post('uid');
        $data['status'] = -1 ;
    
        if (Db::name('users') ->where('id', '=', $id) -> update($data)) { 
            Hook::listen('admin_log', ['用户管理', '删除了用户']);
            return res_json(1); 
        } else {
            return res_json(-1);
        }
      
    }

     /**
     * excel表格导出 
     */
    public function export_excel(Request $request) 
    {
        $get = $request->get();
        $where = [
            ['status', '<>', '-1']
            ,['name', 'LIKE', $get['name'] ?? '']
            ,['phone', 'LIKE', $get['phone'] ?? '']
        ];
        
        $formWhere = $this->parseWhere($where);
        $data = Db::name('users')->where($formWhere)->order('id asc')->select();

        // 导出到excel
        foreach ($data as $k=>&$v) {
            switch ($v['gender']) {
                case 2:
                    $v['gender'] = '女';
                    break;
                case 1:
                    $v['gender'] = '男';
                    break;
                default: 
                    $v['gender'] = '未知';
                    break;
            }
        }

        $cell  = array(
            array('name','姓名'),
            array('phone','手机号'),
            array('gender','性别')
        );

        $title = date('Y-m-d').'用户列表';
        $this->exportSheet($title, $cell, $data);
    }

    /**
     * excel表格导出
     * @param string $title 文件名称
     * @param array $cellName 表头名称
     * @param array $data 要导出的数据
     * @author zhaoyun  
     */
    private function exportSheet($title, $CellName, $data)
    {

        $objPHPExcel = new PHPExcel();
        $cell = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

        $cellNum = count($CellName);
        $dataNum = count($data);

        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell[$i] . 1, $CellName[$i][1]);
            $objPHPExcel->getActiveSheet()->getColumnDimension($cell[$i])->setWidth(18);
        }

        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet()->setCellValue($cell[$j] . ($i + 2), $data[$i][$CellName[$j][0]]);
            }
        }

        $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        
        header('pragma:public');
        $fileName = iconv('utf-8', 'gb2312', $title);//文件名称
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $fileName . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        return true;
    }
}