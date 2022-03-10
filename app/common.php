<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// | Author2: lishuaiqiu(asuma)
// +----------------------------------------------------------------------

// 应用公共文件

//error_reporting(E_ALL ^ E_NOTICE); // 除了 E_NOTICE，报告其他所有错误

use think\response\Json;
use think\route\Url as UrlBuild;

define('APP_VERSION', 'v1.0.0');

if (!function_exists('route')) {
    /**
     * Url生成
     * @param string      $url    路由地址
     * @param array       $vars   变量
     * @return UrlBuild
     */
    function route(string $url = '', array $vars = []): UrlBuild
    {
        return url($url, $vars, false);
    }
}

/**
 * 美化输出print_r
 * @author lishuaiqiu
 */
function i_dump($param)
{
    echo '<pre>';
    print_r($param);
    echo '</pre>';
}

/**
 * 优化的base64encode
 * @author lishuaiqiu
 */
function i_base64encode($string) 
{
    $base64 = base64_encode($string);
    $data = str_replace(array('+','/','='),array('-','_',''),$base64);
    return $data;
}

/**
 * 优化的base64decode
 * @author lishuaiqiu
 */
function i_base64decode($string) 
{
    $data = str_replace(array('-','_'),array('+','/'),$string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

/**
 * 记录日志输出文件，用于程序无法在浏览器打印调试时调用
 * @author lishuaiqiu 
 * @param mixed $output 输出的变量信息
 * @param string $filename 文件名
 * @param string $suffix 文件后缀名
 * @return void
 */
function i_log($output, $filename = '', $suffix = ".log")
{
    $logUrl = "../runtime/ilog/";
    $filename == "" && $filename = date('ymd');

    !is_dir($logUrl) && mkdir($logUrl , 0777 , true);
    
    $head_str = '['.date('H:i:s').'] '.app('http')->getName().'/'.think\facade\Request::controller().'/'.think\facade\Request::action().' '.ucfirst(gettype($output))."\r\n";

    if(!is_string($output)){
        try {
            $output = var_export($output, true);
        } catch (\Exception $e) {
            $output = print_r($output, true);
        }
    };

    file_put_contents($logUrl.$filename.$suffix , $head_str.$output."\r\n" ,FILE_APPEND);
}

/**
 * 升级的md5加密，防止暴力破解
 * +----------------------------------------------------------------------
 * | 加密步骤（加密过程中所有MD5加密后均为32字符十六进制数）：
 * +----------------------------------------------------------------------
 * | 1. 对原字符串进行MD5加密
 * +----------------------------------------------------------------------
 * | 2. 得到加密字符串后，从第九位开始（下标为8），共截取14位字符串
 * +----------------------------------------------------------------------
 * | 3. 使用约定的密钥key,key的第一个字符拼接到14位字符串首位，key的第二个字符拼接到末尾
 * +----------------------------------------------------------------------
 * | 4. 将拼接得到的16位字符串再次使用MD5加密
 * +----------------------------------------------------------------------
 * @author lishuaiqiu
 * @param string $str 要加密的字符串
 * @param string $key 自定义加密key 两位的字符串, 自行记录，以免忘记不可随意更改
 * @return string|bool 返回32位字符十六进制数
 */
function md5safe($str, $key = 'MY')
{
    if (!is_string($key) || strlen($key)<2) return false;

    $en_str = $key[0].substr(md5($str), 8, 14).$key[1];

    return md5($en_str);
}

/**
 * @author lishuaiqiu
 * @return string 返回http或者https
 */
function http_scheme() 
{
    if ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
        return 'https';
    } elseif ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
        return 'https';
    } elseif ( !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return 'https';
    }
    return 'http';
}

/**
 * 表单令牌验证，防止表单重复提交
 * @param array $data 表单数据
 * @author lishuaiqiu
 */
function checkFormToken($data=[])
{
    $token = config('this.form_token');
    if(!isset($data[$token])){
        return false;
    }
    
    $session_token = \think\facade\Session::get($token);

    if(!$token || !$session_token){ //令牌无效
        return false;
    }
    
    if($session_token === $data[$token]){
        return true;
    }

    return false;
}

/**
 * 销毁缓存中的表单令牌
 * @param array $data 表单数据
 * @author lishuaiqiu
 */
function destroyFormToken($data=[])
{
    $token = config('this.form_token');
    if(!isset($data[$token])){
        return false;
    }

    $session_token = \think\facade\Session::get($token);

    if(!$token || !$session_token){ //令牌无效
        return false;
    }

    if($session_token === $data[$token]){
        \think\facade\Session::delete($token);
        return true;
    }

    return false;
}

/**
 * 手机号验证
 * @author lishuaiqiu
 */
function checkPhone($mobile)
{
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^15[^4]{1}\d{8}$|^16[68]{1}\d{8}$|^17[\d]{9}$|^18[\d]{9}$|^19[89]{1}\d{8}$#', $mobile) ? true : false;
}

/**
 * 检查座机号码
 * @author lishuaiqiu
 */
function checkLandline($landline){
    $phoneNum = preg_match('/^((\d{4}|\d{3})-(\d{7,8})|(\d{4}|\d{3})-(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1})|(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1}))$/', $landline);

    return $phoneNum ? true : false;
}

/**
 * 生成随机字符串
 * @param int $length 字符串长度
 * @author lishuaiqiu
 */
function str_rand($length=16): string
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

/**
 * json数据全局统一返回格式（已弃用，用rjson()代替）
 * @deprecated
 * @see rjson()
 * @author lishuaiqiu
 */
function res_json(int $code=100, $result=""): Json
{
    $response = ['code' => $code, 'result' => $result];

    //对新的rjson()方法的接口返回兼容
    if(!is_string($result)){
        $response['data'] = $result;
        $response['msg'] = '';
    }else{
        $response['msg'] = $result;
    }

    return json($response);
}

/**
 * 返回固定json_encode字符串信息（已弃用，用rjson_native()代替）
 * @deprecated
 * @see rjson_native()
 * */
function res_json_native(int $code=100, $result="", $option = JSON_UNESCAPED_UNICODE): string
{
    $response = ['code' => $code, 'result' => $result];

    //对新的rjson()方法的接口返回兼容
    if(!is_string($result)){
        $response['data'] = $result;
        $response['msg'] = '';
    }else{
        $response['msg'] = $result;
    }

    header('Content-Type: application/json');
    return json_encode($response, $option);
}

/**
 * @param int $code 状态码
 * @param string $msg 状态描述信息
 * @param array $data 返回数据信息
 * @author lishuaiqiu
 * json数据全局统一返回格式
 */
function rjson(int $code=100, string $message="", array $data = []): Json
{
    return json(['code' => $code, 'msg' => $message, 'data' => $data]);
}

/**
 * @param int $code 状态码
 * @param string $msg 状态描述信息
 * @param array $data 返回数据信息
 * @param int $option
 * @author lishuaiqiu
 * 返回固定json_encode字符串信息
 * */
function rjson_native(int $code=100, string $message="", array $data = [], $option = JSON_UNESCAPED_UNICODE): string
{
    header('Content-Type: application/json');
    return json_encode(['code' => $code, 'msg' => $message, 'data' => $data], $option);
}

/**
 * Admin后台table数据全局统一返回格式
 * @param int $code 状态码
 * @author lishuaiqiu
 */
function table_json($data = [], $count = 0, $code = 0, $message = "")
{
    //$count <= 10 && $count = 0; //小于10条时隐藏layui分页功能
    return json(['code' => $code, 'msg' => $message, 'count' => $count, 'data' => $data]);
}

/**
 * xss过滤
 * @param string $val 字符串
 */
function off_xss($val) {
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <java\0script>
    // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);
    
    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=@avascript:alert('XSS')>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for($i = 0; $i < strlen ($search); $i ++) {
        // ;? matches the ;, which is optional
        // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars
        
        // @ @ search for the hex values
        $val = preg_replace('/(&#[xX]0{0,8}' . dechex (ord($search[$i])) . ';?)/i', $search[$i], $val); 
        // with a ;
        // @ @ 0{0,7} matches '0' zero to seven times
        $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
    }
    
    // now the only remaining whitespace attacks are \t, \n, and \r
    $ra1 = ['javascript','vbscript','expression','applet','meta','xml','blink','link','style','script','embed','object','iframe','frame','frameset','ilayer','layer','bgsound','title','base'];

    $ra2 = ['onabort','onactivate','onafterprint','onafterupdate','onbeforeactivate','onbeforecopy','onbeforecut','onbeforedeactivate','onbeforeeditfocus','onbeforepaste','onbeforeprint','onbeforeunload','onbeforeupdate','onblur','onbounce','oncellchange','onchange','onclick','oncontextmenu','oncontrolselect','oncopy','oncut','ondataavailable','ondatasetchanged','ondatasetcomplete','ondblclick','ondeactivate','ondrag','ondragend','ondragenter','ondragleave','ondragover','ondragstart','ondrop','onerror','onerrorupdate','onfilterchange','onfinish','onfocus','onfocusin','onfocusout','onhelp','onkeydown','onkeypress','onkeyup','onlayoutcomplete','onload','onlosecapture','onmousedown','onmouseenter','onmouseleave','onmousemove','onmouseout','onmouseover','onmouseup','onmousewheel','onmove','onmoveend','onmovestart','onpaste','onpropertychange','onreadystatechange','onreset','onresize','onresizeend','onresizestart','onrowenter','onrowexit','onrowsdelete','onrowsinserted','onscroll','onselect','onselectionchange','onselectstart','onstart','onstop','onsubmit','onunload'];

    $ra = array_merge($ra1, $ra2);
    
    $found = true; // keep replacing as long as the previous round replaced something
    while ($found == true) {
        $val_before = $val;
        for($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra [$i] [$j];
            }
            $pattern .= '/i';
            $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
            $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
            if ($val_before == $val) {
                // no replacements were made, so exit the loop
                $found = false;
            }
        }
    }
    return htmlspecialchars($val);
}

/*
 * 强制转化为两位小数（保留0）
 * */
function round2($num = 0)
{
    return sprintf("%01.2f", $num);
}

/**
* 截取内容前N张图片（默认截取前三张）
*/
function catch_img($content, $num = 3){
    $imgHtmlReg = '/<img.*\>/isU';
    preg_match_all($imgHtmlReg, $content, $imgHtmlArr);

    $imgSum = count($imgHtmlArr[0]);
    if($imgSum < 1){
        return '';
    }

    $finalImg = [];
    for ($i=0; $i < $imgSum && $i < $num; $i++) { 
        preg_match('/src="(.*)"/isU', $imgHtmlArr[0][$i], $match);

        //过滤百度UEditor中默认的图片
        if(false === strpos($match[1], env('UEDITOR_UPLOAD_PATH'))){
            continue;
        }

        //过滤img标签width属性小于50的图片
        preg_match('/width="(.*)"/isU', $imgHtmlArr[0][$i], $width);
        if($width && $width[1] < 50){
            continue;
        }

        //过滤img标签height属性小于50的图片
        preg_match('/height="(.*)"/isU', $imgHtmlArr[0][$i], $height);
        if($height && $height[1] < 50){
            continue;
        }

        $finalImg[] = $match[1];
    }

    if(empty($finalImg)) return '';

    return json_encode($finalImg, JSON_UNESCAPED_SLASHES);
}