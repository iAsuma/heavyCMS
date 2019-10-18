<?php
namespace util;
use think\facade\Request;
/**
 * 上传封装类，主要为了统一上传路径
 * 修改返回路径为绝对路径
 * 部分方法依赖 topthink/think-image
 * @author li shuaiqiu(asuma)
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
     * 上传并裁剪图片，同时生成压缩图片
     * @access public
     * @param  string|bool      $autoname   生成文件名
     * @param  boolean          $replace 同名文件是否覆盖
     * @param  bool             $autoAppendExt     自动补充扩展名
     * @return false|File       false-失败 否则返回File实例
     */
	public function action($file, $scale=[600, 600], $isGetThumbnail=false, $autoname = true)
	{
		$env_path = Request::env('FILE_ROOT_PATH').Request::env('FILE_UPLOAD_PATH');

		if($env_path){
			$this->file_path = $env_path.DIRECTORY_SEPARATOR.'/temp';
		}

		//上传原始文件
		$info = $file->rule(function($file) use($autoname){
			// $save_path =  date('Ymd') . DIRECTORY_SEPARATOR;
			$save_path = '';

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
		})->move($this->file_path, true, true, true);

		if($info){
			$needPath = date('Ymd') . DIRECTORY_SEPARATOR;
			if(!file_exists($env_path.DIRECTORY_SEPARATOR.$needPath)){
				@mkdir($env_path.DIRECTORY_SEPARATOR.$needPath, 0777, true);
			}

			$savePath = DIRECTORY_SEPARATOR.$needPath.$info->getFilename();
			$savename = $info->getSaveName();
			$full_path = $this->file_path.DIRECTORY_SEPARATOR.$savename;

			$image = \think\Image::open($full_path);

			//裁剪图片
			$crop_path = $env_path.$savePath;
			$image->thumb($scale[0], $scale[1])->save($crop_path);
			
			$thumb_child_path = DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$needPath;
			if(!file_exists($env_path.$thumb_child_path)){
				@mkdir($env_path.$thumb_child_path, 0777, true);
			}

			$thumb_path = $env_path.$thumb_child_path.$savename;
			$image->thumb(100, 100, \think\Image::THUMB_CENTER)->save($thumb_path);

			if(file_exists($crop_path) && file_exists($thumb_path)){
				unset($info);
				@unlink($full_path);
				return [$savePath, $thumb_child_path.$savename];
			}else{
				return false;
			}

		}

		return false;
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
			@mkdir($this->file_path.$child_path, 0777, true);
		}
		
		$full_path = $this->file_path.$child_path.$file_name;
		$result = file_put_contents($full_path, base64_decode($data));

		if(!$result){
			return false;
		}

		$image = \think\Image::open($full_path);
		$thumb_child_path = DIRECTORY_SEPARATOR.'thumb'.$child_path;

		if(!file_exists($this->file_path.$thumb_child_path)){
			@mkdir($this->file_path.$thumb_child_path, 0777, true);
		}

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