<?php
namespace app\admin\controller;

use app\admin\enum\CacheEnum;
use auth\enum\AuthEnum;
use think\facade\Cache;
use think\facade\View;
use think\Request;
use think\facade\Db;
use util\Hook;
/**
 * 权限基础控制器
 * @author asuma(lishuaiqiu) <sqiu_li@163.com>
 */
class AuthSet extends Base
{
    protected $_config = [];

    protected function initialize()
    {
        parent::initialize();
        $this->_config = config('auth.auth_config');
    }

    public function admins()
    {
        $roles = Db::name($this->_config['auth_group'])->field('id,title')->cache('allroles', 24*60*60, CacheEnum::ADMIN_ROLE_TAG)->where('status', 1)->select();
        View::assign('roles', $roles);
    	return View::fetch();
    }

    public function adminList()
    {
        $get = $this->request->get();

        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;

        $where = [
            ['login_name|email|phone', 'LIKE', $get['username'] ?? ''],
            ['name', 'LIKE', $get['truename'] ?? ''],
        ];

        $where = $this->parseWhere($where);

        $query = Db::name($this->_config['auth_user'])->alias('a')->fieldRaw('id,name,login_name,phone,email,FROM_UNIXTIME(create_time, "%Y-%m-%d") AS create_time,status,groupNames')->page($page, $limit)->where('status', '<>', '-1')->order('id');

        $leftTable = Db::name($this->_config['auth_group_access'])->field([
            'acc.uid',
            'GROUP_CONCAT(acc.group_id)' => 'groupIds',
            'GROUP_CONCAT(g.title)' => 'groupNames'
        ])->alias('acc')->leftjoin($this->_config['auth_group'].' g', 'acc.group_id=g.id')->where('g.status', '<>', '-1')->group('acc.uid')->buildSql();

        $query->leftjoin($leftTable.'b', 'a.id=b.uid');
        $query->where($where);

        if($get['role'] ?? ''){
            $query->whereRaw('CONCAT(",", groupIds, ",") LIKE :groupIds', ['groupIds' => '%,'.$get['role'].',%']);
        }

        $admin = $query->select();

        $countQuery = Db::name($this->_config['auth_user'])->alias('a')->where($where)->where('is_delete', '=', 0);
        if($get['role'] ?? ''){
            $countQuery->leftjoin($leftTable.'b', 'a.id=b.uid');
            $countQuery->whereRaw('CONCAT(",", groupIds, ",") LIKE :groupIds', ['groupIds' => '%,'.$get['role'].',%']);
        }
        $count = $countQuery->count();
        
        return table_json($admin, $count);
    }

    public function adminEdit()
    {
        if($this->request->get('id')){
            $cacheKey= md5('adminUser_'.(int)$this->request->get('id'));
            $adminInfo = Db::name($this->_config['auth_user'])->where('id', (int)$this->request->get('id'))->cache($cacheKey, 24*60*60, AuthEnum::CACHE_ADMIN_TAG)->find();
            $hasRole = Db::name($this->_config['auth_group_access'])->where('uid', (int)$this->request->get('id'))->select();
            $hasRoleId = array_column($hasRole->toArray(), 'group_id');
        }

        $roles = Db::name($this->_config['auth_group'])->field('id,title')->cache('allroles', 24*60*60, CacheEnum::ADMIN_ROLE_TAG)->where('status', 1)->select();

        View::assign('admin', $adminInfo ?? []);
        View::assign('hasrole', $hasRoleId ?? []);
        View::assign('roles', $roles);
        return View::fetch();
    }

    public function pulladmin(Request $request)
    {
        if(checkFormToken($request->post())){
            $validate = new \app\admin\validate\Register;
            if(!$validate->scene('register')->check($request->post())){
                return res_json(-1, $validate->getError());
            }

            Db::startTrans();
            try {
                $data = [
                    'login_name' => $request->post('loginname'),
                    'name' => $request->post('truename'),
                    'phone' => $request->post('phone'),
                    'email' => $request->post('email'),
                    'password' => md5safe(config('this.admin_init_pwd')),
                    'status' => $request->post('status') ?: 0,
                    'create_time' => time(),
                    'create_by' => $request->uid
                ];

                $where = $this->parseWhere([
                    ['login_name', '=', $data['login_name']],
                    ['email', '=', $data['email']],
                    ['phone', '=', $data['phone']]
                ]);

                $loginUser = Db::name($this->_config['auth_user'])
                ->field('id,name,login_name,phone,email')
                ->where(function($query) use($where){
                    $query->whereOr($where);
                })
                ->where('status', '<>', -1)
                ->select();

                if(in_array($data['login_name'], array_column($loginUser, 'login_name'))) return res_json(-3, '用户名已存在');
                if(in_array($data['phone'], array_column($loginUser, 'phone'))) return res_json(-3, '手机号已注册');
                if(in_array($data['email'], array_column($loginUser, 'email'))) return res_json(-3, '邮箱已注册');

                $new_id = Db::name($this->_config['auth_user']) -> insertGetId($data);
                if(!$new_id) return res_json(-6, '添加失败');

                $roleArr= explode(',', $request->post('roles'));
                foreach ($roleArr as $v) {
                    $access[] = [
                        'uid' => $new_id,
                        'group_id' => $v
                    ];
                }

                $result = Db::name($this->_config['auth_group_access'])->insertAll($access);

                if(!$result){
                    Db::rollback();
                    return res_json(-4, '添加失败');
                }

                Hook::listen('admin_log', ['权限', '添加了管理员'.$data['login_name']]);
                
                Db::commit();
                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                Db::rollback();
                return res_json(-5, '系统错误');
            }
        }

        return res_json(-2, '请勿重复提交');
    }

    public function updateAdmin(Request $request)
    {
        if(empty($request->post('admin_id'))) return res_json(-2, '非法修改');

        if(checkFormToken($request->post())){
            $validate = new \app\admin\validate\Register;
            if(!$validate->scene('register')->check($request->post())){
                return res_json(-1, $validate->getError());
            }

            Db::startTrans();
            try {
                $data = [
                    'login_name' => $request->post('loginname'),
                    'name' => $request->post('truename'),
                    'phone' => $request->post('phone'),
                    'email' => $request->post('email'),
                    'update_time' => time(),
                    'update_by' => $this->uid
                ];

                if($request->post('admin_id') != 1){
                    $data['status'] = $request->post('status') ?: 0;
                }

                if($request->post('isReSetPwd') ?? ''){
                    $data['password'] = md5safe(config('this.admin_init_pwd'));
                }

                $where = $this->parseWhere([
                    ['login_name', '=', $data['login_name']],
                    ['email', '=', $data['email']],
                    ['phone', '=', $data['phone']]
                ]);

                $loginUser = Db::name($this->_config['auth_user'])
                ->field('id,name,login_name,phone,email')
                ->where(function($query) use($where){
                    $query->whereOr($where);
                })
                ->where('id', '<>', $request->post('admin_id'))
                ->where('status', '<>', -1)
                ->select();

                if(in_array($data['login_name'], array_column($loginUser, 'login_name'))) return res_json(-3, '用户名已存在');
                if(in_array($data['phone'], array_column($loginUser, 'phone'))) return res_json(-3, '手机号已注册');
                if(in_array($data['email'], array_column($loginUser, 'email'))) return res_json(-3, '邮箱已注册');

                $update = Db::name($this->_config['auth_user']) ->where('id', $request->post('admin_id')) -> update($data);
                if($update === false) return res_json(-6, '修改失败');

                $roleArr= explode(',', $request->post('roles'));
                foreach ($roleArr as $v) {
                    $access[] = [
                        'uid' => $request->post('admin_id'),
                        'group_id' => $v
                    ];
                }

                if($request->post('admin_id') != 1){
                    Db::name($this->_config['auth_group_access'])->where('uid', (int)$request->post('admin_id'))->delete();
                    $result = Db::name($this->_config['auth_group_access'])->insertAll($access);

                    $cacheKey = 'group_1_'.$request->post('admin_id');
                    Cache::rm($cacheKey); //清除用户组缓存，权限实时生效
                    Cache::tag(AuthEnum::CACHE_ADMIN_TAG)->clear(); //清除用户数据缓存

                    if(!$result){
                        Db::rollback();
                        return res_json(-4, '添加失败');
                    }
                }
                
                Hook::listen('admin_log', ['权限', '修改了管理员'.$data['login_name'].'的信息']);

                Db::commit();
                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                Db::rollback();
                return res_json(-5, '系统错误'.$e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

    public function changeAdminStatus()
    {
        $id = (int)$this->request->post('id');
        $uid = $this->request->uid;

        $data = [];
        switch ($this->request->post('status')) {
            case 'true':
                $data['status'] = 1;
                $data['update_time'] = time();
                $data['update_by'] = $this->uid;
                $err_msg = '状态修改失败';
                $log_str = '开启了管理员'.$this->request->post('name').'的账号';
                break;
            case 'delete':
                $data['is_delete'] = 1;
                $data['delete_date'] = date('Y-m-d H:i:s');
                $err_msg = '删除失败';
                $log_str = '删除了管理员'.$this->request->post('name');
                break;
            default:
                $data['status'] = -2;
                $data['update_time'] = time();
                $data['update_by'] = $uid;
                $err_msg = '状态修改失败';
                $log_str = '冻结了管理员'.$this->request->post('name').'的账号';
                break;
        }

        $id && $res = Db::name($this->_config['auth_user'])->where('id', '=', $id)->update(['status' => $data]);

        if(!$res) return res_json(-3, $err_msg);
        Hook::listen('admin_log', ['权限', $log_str]);
        
        Cache::tag(AuthEnum::CACHE_ADMIN_TAG)->clear(); //清除用户数据缓存
        return res_json(1);
    }

    public function roles()
    {
    	return View::fetch();
    }

    public function roleList()
    {
        $get = $this->request->get();

        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;

        $where = [
            ['status', '<>', '-1']
        ];

        $formWhere = $this->parseWhere($where);
        $cacheKey = 'role_'.md5(http_build_query($formWhere));
        
        $count = Db::name($this->_config['auth_group'])->where($formWhere)->cache($cacheKey.'_count', 24*60*60, CacheEnum::ADMIN_ROLE_TAG)->count('id');
        $roles = Db::name($this->_config['auth_group'])->where($formWhere)->order('id')->page($page, $limit)->cache($cacheKey.'_'.$page.'_'.$limit, 24*60*60, CacheEnum::ADMIN_ROLE_TAG)->select();

        return table_json($roles, $count);
    }

    public function roleAdd(Request $request)
    {
        if($request->get('id')){
            $roleInfo = Db::name($this->_config['auth_group'])->where('id', (int)$request->get('id'))->find();

            View::assign('role', $roleInfo);
            return View::fetch('role_edit');
        }

        return View::fetch();
    }

    public function allrules()
    {
        $rules = Db::name($this->_config['auth_rule'])->where('status', 1)->order(['sorted', 'id'])->cache('use_rules', 24*60*60, AuthEnum::CACHE_RULE_TAG)->select();

        $tree = new \util\Tree($rules);
        $mods = $tree->leaf();

        return rjson(0,'', $mods);
    }

    public function rulesChecked()
    {
        $rulesArr = explode(",", $this->request->get('rules'));

        $allrules = Db::name($this->_config['auth_rule'])->where('status', 1)->order(['sorted', 'id'])->cache('use_rules', 24*60*60, AuthEnum::CACHE_RULE_TAG)->select();

        foreach ($allrules as &$v) {
            in_array($v['id'], $rulesArr) && $v['checked'] = true;
        }

        $tree = new \util\Tree($allrules);
        $mods = $tree->leaf();
        return rjson(0, '', $mods);
    }

    public function addNewRole(Request $request)
    {
        try {
            $post = $request->post();
            if(!checkFormToken($post)) return res_json(-2, '请勿重复提交');

            $rules = $post['rules'] ?? [];
            if(empty($rules)) return res_json(-3, '请为角色选择规则节点');
            sort($rules);
            $rules = implode(",", $rules);

            $data = [
                'title' => off_xss(trim($post['rolename'])),
                'rules' => $rules,
                'status' => $post['status'] ?? -2,
                'remark' => $post['desc']
            ];

            $validate = \util\Validate::make([
                'title' => 'require|max:30',
                'remark' => 'max:200',
            ],[
                'title.require'=> '请填写角色名',
                'title.max'    => '角色名最多不能超过30个字符',
                'remark'       => '描述最多不能超过200个字符',
            ]);

            if(!$validate->check($data)){
                return res_json(-4, $validate->getError());
            }

            if($post['role_id'] ?? ''){
                $result = Db::name($this->_config['auth_group']) ->where('id', $post['role_id']) -> update($data);
                if($result === false) return res_json(-1, '修改失败');
                Hook::listen('admin_log', ['权限', '修改了角色组'.$data['title'].'的信息']);
            }else{
                $result = Db::name($this->_config['auth_group']) -> insert($data);
                if(!$result) return res_json(-1, '添加失败');
                Hook::listen('admin_log', ['权限', '添加了角色组'.$data['title']]);
            }
            
            Cache::tag(AuthEnum::CACHE_RULE_TAG)->clear();
            Cache::tag(CacheEnum::ADMIN_ROLE_TAG)->clear(); //清除规则缓存，让列表实时生效
            destroyFormToken($post);
            return res_json(1);
        } catch (\Exception $e) {
            return res_json(-100, $e->getMessage());
        }
    }

    public function changeRoleStatus()
    {
        $id = (int)$this->request->post('id');
        $uid = $this->request->uid;
        $pwd = $this->request->post('password');

        $cacheKey= md5('adminUser_'.$uid);
        $uid && $user = Db::name($this->_config['auth_user'])->where('id', '=', $uid)->cache($cacheKey, 24*60*60, AuthEnum::CACHE_ADMIN_TAG)->find();
        if(empty($user)) return res_json(-1, '用户信息获取失败，请重新登录');
        
        switch ($this->request->post('status')) {
            case 'true':
                $status = 1;
                break;
            case 'delete':
                $status = -1;
                break;
            default:
                $status = -2;
                break;
        }

        if(!empty($pwd) && $user['password'] != md5safe($pwd)){
            return res_json(-2, '密码错误');
        }

        $id && $res = Db::name($this->_config['auth_group'])->where('id', '=', $id)->update(['status' => $status]);

        if($status == -1){
            if(!$res) return res_json(-3, '删除失败');
            Hook::listen('admin_log', ['权限', '删除了角色组'.$this->request->post('name')]);
        }else{
            if(!$res) return res_json(-3, '状态切换失败');
            Hook::listen('admin_log', ['权限', ($status == -2 ? '关闭了角色组' :'开启了角色组').$this->request->post('name')]);
        }
        
        Cache::tag(CacheEnum::ADMIN_ROLE_TAG)->clear(); //清除规则缓存，让列表实时生效
        return res_json(1);
    }

    public function permissions()
    {
        return View::fetch();
    }

    public function permissionsList()
    {
        $get = $this->request->get();

        $where = [
            ['status', '<>', '-1'],
            ['is_menu', '=', (isset($get['is_menu']) && !empty($get['is_menu'])) ? 1 : ''],
            ['name', 'LIKE', $get['name'] ?? ''],
            ['title', 'LIKE', $get['title'] ?? '']
        ];

        $formWhere = $this->parseWhere($where);

        $cacheKey = 'rule_'.md5(http_build_query($formWhere));
        $count = Db::name($this->_config['auth_rule'])->where($formWhere)->cache($cacheKey.'_count', 24*60*60, AuthEnum::CACHE_RULE_TAG)->count('id');

        if(empty($count)){
            return table_json([], 0);
        }

        // 查询所有规则，用以排序子父级关系，并存入缓存(tag:auth_rule)
        $rules = Db::name($this->_config['auth_rule'])->where($formWhere)->order(['sorted', 'id'])->cache($cacheKey, 24*60*60, AuthEnum::CACHE_RULE_TAG)->select();

        $mark = count($formWhere);
        if(($where[1][2] && $mark > 2) || (!$where[1][2] && $mark > 1)) {
            $modsTree = $rules;
        }else{
            $tree = new \util\Tree($rules);
            $modsTree = $tree->table();
        }

        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;

        $list = array_slice($modsTree, ($page-1)*$limit, $limit);
        
        return table_json($list, count($modsTree));
    }

    public function authAdd()
    {
        return View::fetch();
    }

    public function modsTree()
    {
        $where['status'] = 1;
        if($this->request->post('type') == 3){
            $where['type'] = [1,2];
        }else{
            $where['type'] = 1;
        }

        $mods = Db::name($this->_config['auth_rule'])->field('id,title,name,pid')->where($where)->order('sorted,id')->select();

        if(empty($mods)){
            return null;
        }
        
        $tree = new \util\Tree($mods);
        $modsTree = $tree->leaf();

        return rjson(1, '', $modsTree);
    }

    public function pullRule(Request $request)
    {
        try {
            $post = $request->post();
            if(!checkFormToken($post)) return res_json('-2', '请勿重复提交');

            $data = [
                'name' => off_xss(trim($post['authname'])),
                'title' => off_xss(trim($post['authtitle'])),
                'type' => (int)$post['type'],
                'run_type' => (int)$post['run_type'],
                'status' => $post['status'] ?? -2,
                'sorted' => $post['run_type'] == 2 ? 99 : (int)$post['sorted'],
                'pid' => (int)$post['pId'],
                'is_menu' => $post['is_menu'] ?? 0,
                'icon' => $post['icon'] ?? '',
                'is_logged' => $post['is_log'] ?? 0,
                'remark' => off_xss(trim($post['desc']))
            ];

            $validate = \util\Validate::make([
                'name' => 'require|max:50',
                'title' => 'require|max:30',
                'remark' => 'max:200',
            ],[
                'name.require' => '请填写规则标识',
                'name.max'     => '规则标识最多不能超过50个字符',
                'title.require'=> '请填写权限名',
                'title.max'    => '权限名最多不能超过30个字符',
                'remark'       => '描述最多不能超过200个字符',
            ]);

            if(!$validate->check($data)){
                return res_json(-3, $validate->getError());
            }

            $result = Db::name($this->_config['auth_rule']) -> insert($data);
            if(!$result) return res_json(-1, '添加失败');

            destroyFormToken($post);
            Cache::tag(AuthEnum::CACHE_RULE_TAG)->clear(); //清除规则缓存，让列表实时生效
            return res_json(1);
        } catch (\Exception $e) {
            $msg = false !== strpos($e->getMessage(), '1062') ? '权限标识重复' : $e->getMessage();
            return res_json(-100, $msg);
        }
    }

    public function changeLogStatus()
    {
        $id = (int)$this->request->post('id');
        $is_logged = $this->request->post('is_logged');

        $is_logged = $is_logged == 'true' ? 1 : 0;
        $res = Db::name($this->_config['auth_rule'])->where('id', '=', $id)->update(['is_logged' => $is_logged]);
        Cache::tag(AuthEnum::CACHE_RULE_TAG)->clear(); //清除规则缓存，让列表实时生效
        if(!$res) return res_json(-3, '切换失败');

        return res_json(1);
    }

    public function changeWeight()
    {
        $post = $this->request->post();
        if($post['is_menu'] != 1) return res_json(-1, '非菜单无法设置权重');

        $post['id'] && $res = Db::name($this->_config['auth_rule'])->where('id', '=', (int)$post['id'])->update(['sorted' => (int)$post['newVal']]);
        if(!$res) return res_json(-3, '修改失败');
        Cache::tag(AuthEnum::CACHE_RULE_TAG)->clear(); //清除规则缓存，让列表实时生效

        return res_json(1);
    }

    public function changeRuleStatus()
    {
        $id = (int)$this->request->post('id');
        $uid = $this->request->uid;
        $pwd = $this->request->post('password');
        $statusMark = $this->request->post('status');

        $cacheKey= md5('adminUser_'.$uid);
        $uid && $user = Db::name($this->_config['auth_user'])->where('id', '=', $uid)->cache($cacheKey, 24*60*60, AuthEnum::CACHE_ADMIN_TAG)->find();
        if(empty($user)) return res_json(-1, '用户信息获取失败，请重新登录');

        if($statusMark == 'true'){
            $status = 1;
        }else if($statusMark == 'delete'){
            $status = -1;

            if($user['password'] != md5safe($pwd)) return res_json(-2, '密码错误');

            $info = Db::name($this->_config['auth_rule'])->field('id,name')->where('pid' , $id)->select();
            if(!empty($info)) return res_json(-2, '请先删除子权限');
        }else{
            $status = -2;
        }

        if($status == -1){
            $id && $res = Db::name($this->_config['auth_rule'])->delete($id);
            if(!$res) return res_json(-3, '删除失败');
        }else{
            $id && $res = Db::name($this->_config['auth_rule'])->where('id', '=', $id)->update(['status' => $status]);
            if(!$res) return res_json(-3, '状态切换失败');
        }
        
        Cache::tag(AuthEnum::CACHE_RULE_TAG)->clear(); //清除规则缓存，让列表实时生效

        return res_json(1);
    }

    public function authEdit()
    {
        $id = (int)$this->request->get('rule');
        $id && $info = Db::name($this->_config['auth_rule'])->where(['id' => $id])->find();

        isset($info) && View::assign('info', $info);
        
        return View::fetch();
    }

    public function editRule(Request $request)
    {
        try {
            $post = $request->post();
            if(!checkFormToken($post)) return res_json('-2', '请勿重复提交');

            $data = [
                'title' => off_xss(trim($post['authtitle'])),
                'name' => off_xss(trim($post['authname'])),
                'status' => $post['status'] ?? -2,
                'sorted' => $post['sorted'] ?? 99,
                'pid' => (int)$post['pId'],
                'is_menu' => $post['is_menu'] ?? 0,
                'icon' => $post['icon'] ?? '',
                'is_logged' => $post['is_log'] ?? 0,
                'remark' => off_xss(trim($post['desc']))
            ];

            $validate = \util\Validate::make([
                'title' => 'require|max:30',
                'remark' => 'max:200',
            ],[
                'title.require'=> '请填写权限名',
                'title.max'    => '权限名最多不能超过30个字符',
                'remark'       => '描述最多不能超过200个字符',
            ]);

            if(!$validate->check($data)){
                return res_json(-3, $validate->getError());
            }

            $result = Db::name($this->_config['auth_rule'])->where('id', (int)$post['rule_id'])->update($data);
            if($result === false) return res_json(-1, '修改失败');

            destroyFormToken($post);
            Cache::tag(AuthEnum::CACHE_RULE_TAG)->clear(); //清除规则缓存，让列表实时生效
            return res_json(1);
        } catch (\Exception $e) {
            return res_json(-100, $e->getMessage());
        }
    }

    public function operationLog()
    {
        return View::fetch();
    }

    public function logList()
    {
        $get = $this->request->get();
        
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;

        $where = [];
        if(isset($get['username']) && !empty($get['username'])){
            $where[] = ['behavior_user', '=', $get['username']];
        }
        
        $countQuery = Db::name('operation_log')->where($where);
        $query = Db::name('operation_log')->where($where)->order('id DESC')->page($page, $limit);

        if(isset($get['datetime']) && !empty($get['datetime'])){
            $date = explode('~', $get['datetime']);
            $get['start'] = $date[0];
            $countQuery->whereTime('record_time', 'between', [$date[0].' 00:00:00', $date[1].' 23:59:59']);
            $query->whereTime('record_time', 'between', [$date[0].' 00:00:00', $date[1].' 23:59:59']);
        }

        $count = $countQuery->count('id');
        $logs = $query->select();

        return table_json($logs, $count);
    }

    public function batchDeleteLogs()
    {
        $ids = $this->request->post('ids');
        if(empty($ids)) return res_json(-1, '请选择要删除的数据');

        $uid = $this->request->uid;
        $pwd = $this->request->post('password');

        $cacheKey= md5('adminUser_'.$uid);
        $uid && $user = Db::name($this->_config['auth_user'])->where('id', '=', $uid)->cache($cacheKey, 24*60*60, AuthEnum::CACHE_ADMIN_TAG)->find();
        if(empty($user)) return res_json(-1, '用户信息获取失败，请重新登录');

        if($user['password'] != md5safe($pwd)) return res_json(-2, '密码错误');

        $result = Db::name('operation_log')->where('id', 'IN', $ids)->delete();
        if(!$result) return res_json(-1, '删除失败');

        return res_json(1);
    }
}