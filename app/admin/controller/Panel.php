<?php

namespace app\admin\controller;

use app\common\BaseController;
use think\facade\View;

class Panel extends BaseController
{

    public function index()
    {
        return View::fetch();
    }
}
