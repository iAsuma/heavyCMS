<?php
namespace app\common;

// 应用请求对象类
class Request extends \think\Request
{
    public function checkRouteInList(array $routeList = []): bool
    {
        if(empty($routeList)){
            return false;
        }

        foreach ($routeList as $v) {
            if(strtolower($v) == $this->pathinfo() || $this->isMapBaseUrl($v)){
                return true;
            }
        }

        return false;
    }

    public function isMapBaseUrl(string $pattern): bool
    {
        $patternArr = explode('/', $pattern);
        if(count($patternArr) == 3){
            if(strtolower($patternArr[0]) == strtolower(app('http')->getName()) && strtolower($patternArr[1]) == strtolower($this->controller()) && strtolower($patternArr[2]) == strtolower($this->action())){
                return true;
            }
        }else if(count($patternArr) == 2){
            if(strtolower($patternArr[0]) == strtolower(app('http')->getName()) && strtolower($patternArr[1]) == strtolower($this->controller())){
                return true;
            }
        }else{
            if(strtolower($patternArr[0]) == strtolower(app('http')->getName())){
                return true;
            }
        }

        return false;
    }
}
