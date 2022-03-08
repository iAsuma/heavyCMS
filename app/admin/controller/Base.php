<?php
namespace app\admin\controller;

use app\common\BaseController;
use PHPExcel;
use PHPExcel_IOFactory;

/**
 * 业务基类
 * @author asuma(lishuaiqiu) <sqiu_li@163.com>
 */
class Base extends BaseController
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
                $objPHPExcel->getActiveSheet()->setCellValue($cell[$j] . ($i + 2), $data[$i][$CellName[$j][0]]);
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
