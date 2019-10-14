<?php

namespace app\index\controller;

use Db;
use think\Controller;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;

class Camera extends Controller
{
    
    public function index($code="")
    {
        if(empty($code)){
            exception('非法访问');
        }

        $info = Db::name('camera_info')->where('serial_no', '=', $code)->where('status' , '<>' , -1)->find();
        empty($info) && exit('<h2>未获取到信息</h2>');
        
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function createUrlQrcode($code="")
    {
        $url = $this->request->domain();
        $url .= '/code/';
        $url .= $code;

        $qrCode = new QrCode($url);

        $qrCode->setMargin(0);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setWriterByName('png');
        
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel(ErrorCorrectionLevel::HIGH));
        $qrCode->setValidateResult(false);

        if(request()->get('down')){
            $qrCode->setSize(128);

            $camera_info = Db::name('camera_info')->field('serial_no,location,ipv4')->where('serial_no', '=', $code)->where('status' , '<>' , -1)->find();
            $qrcode_path = env('FILE_ROOT_PATH').env('FILE_UPLOAD_PATH').'/qrcode/';
            $qrcode_name = $camera_info['ipv4'].'_'.$camera_info['location'].'_'.$code.'.png';
            $qrCode->writeFile($qrcode_path.$qrcode_name);

            header('Content-Type: '.$qrCode->getContentType());
            header('Content-Disposition:attachment;filename=' . basename($qrcode_path.$qrcode_name));
            header('Content-Length:' . filesize($qrcode_path.$qrcode_name));
            //读取文件并写入到输出缓冲
            readfile($qrcode_path.$qrcode_name);
        }else{
            $qrCode->setSize(150);
            header('Content-Type: '.$qrCode->getContentType());
            echo $qrCode->writeString();
        }

        exit();
    }

}
