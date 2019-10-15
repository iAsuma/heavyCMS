<?php
namespace util;
use think\facade\Request;
/**
 * 上传封装类，主要为了统一上传路径
 * 修改返回路径为绝对路径
 * 部分方法依赖 topthink/think-image
 */
class Upload 
{
	public $file_path = './uploads';

	public function file($fileType = '')
	{
		return Request::file($fileType);
	}

	/**
     * 重写移动文件方法
     * @access public
     * @param  string|bool      $autoname   生成文件名
     * @param  boolean          $replace 同名文件是否覆盖
     * @param  bool             $autoAppendExt     自动补充扩展名
     * @return false|File       false-失败 否则返回File实例
     */
	public function move($file, $autoname = true, $replace = true, $autoAppendExt = true)
	{
		$env_path = Request::env('FILE_ROOT_PATH').Request::env('FILE_UPLOAD_PATH');

		if($env_path){
			$this->file_path = $env_path;
		}

		$info = $file->rule(function($file) use($autoname){
			$save_path =  date('Ymd') . DIRECTORY_SEPARATOR;

			if(true === $autoname){
				// md5 生成文件名
				$save_path .= md5(microtime(true).rand(1000, 9999));
			}elseif (false === $autoname || '' === $autoname){
				// 保留原文件名
				$save_path .= $file->getInfo('name');
			}else{
				// 自定义文件名
				$save_path .= (string)htmlspecialchars($autoname);
			}

			return $save_path;
		})->move($this->file_path, $savename=true, $replace, $autoAppendExt);

		$info && $info->savePath = DIRECTORY_SEPARATOR.$info->getSaveName();

		return $info;
	}

	/**
     * base64信息转为图片
     * @access public
     * @param  string      $base64Str  图片base
     * @param  boolean     $isGetThumbnail 是否同时生成缩略图
     */
	public function base64ToImage($base64Str, $isGetThumbnail=false)
	{
		$env_path = Request::env('FILE_ROOT_PATH').Request::env('FILE_UPLOAD_PATH');

		if($env_path){
			$this->file_path = $env_path;
		}

		if(!is_dir($this->file_path) && !mkdir($this->file_path, 0777, true)){
			throw new \think\Exception("文件目录没有权限");
		}

		list($type, $data) = explode(',', $base64Str);
		$ext = str_replace(['data:image/', ';base64'], '', $type);
		$ext == 'jpeg' && $ext = 'jpg';

		$child_path = DIRECTORY_SEPARATOR.date('Ymd').DIRECTORY_SEPARATOR;
		$file_name = md5(microtime(true).rand(1000, 9999)).'.'.$ext;

		if(!file_exists($this->file_path.$child_path)){
			mkdir($this->file_path.$child_path, 0777, true);	
		}
		
		$full_path = $this->file_path.$child_path.$file_name;
		$result = file_put_contents($full_path, base64_decode($data));

		if(!$result){
			return false;
		}

		if($isGetThumbnail){
			// coding...
		}

		return [$full_path, $child_path.$file_name, $file_name];
	}

	/**
     * base64信息转为缩略图片
     * @access public
     * @param  string      $base64Str  图片base
     */
	public function base64ToThumbnailImage($base64Str, $scale=[150, 150], $thumbType = \think\Image::THUMB_CENTER)
	{
		$env_path = Request::env('FILE_ROOT_PATH').Request::env('FILE_UPLOAD_PATH');

		if($env_path){
			$this->file_path = $env_path;
		}

		if(!is_dir($this->file_path) && !mkdir($this->file_path, 0777, true)){
			throw new \think\Exception("文件目录没有权限");
		}

		list($type, $data) = explode(',', $base64Str);
		$ext = str_replace(['data:image/', ';base64'], '', $type);
		$ext == 'jpeg' && $ext = 'jpg';

		$child_path = DIRECTORY_SEPARATOR.date('Ymd').DIRECTORY_SEPARATOR;
		$file_name = md5(microtime(true).rand(1000, 9999)).'.'.$ext;

		if(!file_exists($this->file_path.$child_path)){
			mkdir($this->file_path.$child_path, 0777, true);	
		}
		
		$full_path = $this->file_path.$child_path.$file_name;
		$result = file_put_contents($full_path, base64_decode($data));

		if(!$result){
			return false;
		}

		$image = \think\Image::open($child_path.$file_name);
		$thumb_child_path = DIRECTORY_SEPARATOR.'thumb'.$child_path;
		$thumb_path = $this->file_path.$thumb_child_path.$file_name;
		if($thumbType !== false){
			$image->thumb($scale[0], $scale[1], $thumbType)->save($thumb_path);
		}else{
			$image->thumb($scale[0], $scale[1])->save($thumb_path);
		}

		if(file_exists($thumb_path)){
			@unlink($full_path);
		}else{
			return false;
		}

		return [$thumb_path, $thumb_child_path.$file_name, $file_name];
	}
}