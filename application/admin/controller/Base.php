<?php

namespace app\admin\controller;

use think\Controller;
use PHPExcel;
use PHPExcel_IOFactory;
class Base extends Controller
{
    protected $uid = NULL;
    protected $uname = NULL;
    protected $ulogin = NULL;

    protected function initialize()
    {
        $this->uid = $this->request->uid;
        $this->uname = $this->request->uname;
        $this->ulogin = $this->request->ulogin;
    }

    public function parseWhere($where): array
    {
        unset($where['limit']);
        unset($where['page']);

        if(empty($where)) return [];

        $condition = [];
        foreach ($where as $v) {
            if(strlen($v[2]) < 1){
                continue;
            }
            
            $v2 = &$v[2];
            in_array(strtoupper($v[1]), ['LIKE', 'NOT LIKE']) &&  $v2 =  '%'.$v2.'%';
            $condition[] = $v;
        }

        return $condition;
    }
    
    /**
     * @param $code 状态码
     * @author lishuaiqiu
     * Admin后台table数据全局统一返回格式
     */
    public function table_json($data = [], $count = 0, $code = 0, $msg = "")
    {
        //$count <= 10 && $count = 0; //小于10条时隐藏layui分页功能
        return json(['code' => $code, 'msg' => $msg, 'count' => $count, 'data' => $data]);
    }

    /**
     * @param $code 状态码
     * @author lishuaiqiu
     * Admin后台json数据全局统一返回格式
     */
    public function res_json(int $code=100, $result="")
    {
        return json(['code' => $code, 'result' => $result]);
    }

    /**
    *   返回请求结果状态
    */
    public function res_json_str($code='101', $result='')
    {
        $data = ['code' => $code, 'result' => $result];
        return $this->json($data);
    }

    /**
    *   返回json字符串数据
    */
    public function json($data)
    {
        return json()->data($data)->getContent();
    }

    /**
     * excel表格导出
     * @param string $title 文件名称
     * @param array $cellName 表头名称
     * @param array $data 要导出的数据
     * @author zhaoyun  
     */
    public function exportExcel($title, $CellName, $data) 
    {

        $objPHPExcel = new PHPExcel();
        $cell = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

        $cellNum = count($CellName);
        $dataNum = count($data);
        // $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cell[$cellNum - 1] . '1');//合并单元格
        // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $title);

        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell[$i] . 1, $CellName[$i][1]);
        }

        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cell[$j] . ($i + 2), $data[$i][$CellName[$j][0]]);   
            }
        }
      

        header('pragma:public');
        $fileName = iconv('utf-8', 'gb2312', $title);//文件名称
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $fileName . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

}
