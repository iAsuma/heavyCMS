<?php
use think\facade\Route;

Route::group('/', function (){
    Route::get('/', 'admin/index/index');

    Route::rule('ueditor' , 'admin/index/ueditor');
    Route::rule('layuiUpload', 'admin/index/layuiUpload');

    // 登录路由组
    Route::group('login' ,function (){
        Route::get('/', 'admin/Login/index'); //登录页
        Route::post('check', 'admin/Login/checkLogin'); //登录
        Route::rule('logout', 'admin/Login/logout'); //退出登录
    });

    // 面板路由组
    Route::group('panel', function (){
        Route::get('/', 'Panel/index'); // 面板首页
    });

    // 组件管理
    Route::group('element', function (){
        Route::get('banner$', 'Element/banner'); //轮播图首页
        Route::get('banner/list', 'Element/bannerList'); //轮播图列表
        Route::get('banner/add_index', 'Element/bannerAdd');//轮播图添加页
        Route::post('banner/add', 'Element/addBanner'); //添加轮播图
        Route::get('banner/edit_index', 'Element/bannerEdit'); //轮播图修改页
        Route::post('banner/edit', 'Element/editBanner'); //修改轮播图
        Route::post('banner/del', 'Element/bannerDel'); //删除轮播图
        Route::post('banner/change_weight', 'Element/changeWeight'); //设置轮播图权重值
    });

    // 内容管理
    Route::group('contents', function (){
        Route::get('articles$', 'Contents/articles'); //文章首页
        Route::get('articles_list', 'Contents/articlesList'); //文章列表
        Route::get('articles_add', 'Contents/articlesAdd'); //文章添加页
        Route::get('articles_add2', 'Contents/articlesAdd2'); //文章添加页
        Route::post('articles/add', 'Contents/addArticles'); //添加文章
        Route::post('articles/edit', 'Contents/editArticles'); //编辑文章
        Route::post('articles/del', 'Contents/del'); //删除文章
        Route::post('articles/change_status', 'Contents/changeArticlesStatus'); //修改文章状态
        Route::get('article/detail', 'Contents/articleDetail'); //文章详情

        Route::get('column$', 'Contents/column'); // 栏目管理首页
        Route::get('column/list', 'Contents/columnList'); //栏目列表
        Route::get('column_add', 'Contents/columnAdd'); //栏目添加页
        Route::post('column/add', 'Contents/addColumn'); //添加栏目
        Route::get('column_edit', 'Contents/columnEdit'); //栏目编辑页
        Route::post('column/edit', 'Contents/editColumn'); // 编辑栏目
        Route::post('column/del', 'Contents/columnDel'); //删除栏目
        Route::post('column/change_status', 'Contents/changeColumnStatus'); //修改栏目状态
    });

    // C端用户管理
    Route::group('user', function (){
        Route::get('index', 'User/index'); //用户管理首页
        Route::get('list', 'User/userList'); //用户管理列表
        Route::get('edit_index', 'User/userEdit'); //用户编辑页
        Route::post('edit', 'User/editUser'); //编辑用户
        Route::post('del', 'User/del'); //删除用户
    });

    // 接入应用配置
    Route::group('basis', function (){
       Route::get('appset', 'BasisSet/appset'); //应用设置首页
        Route::get('app/list', 'BasisSet/appList'); //应用列表
        Route::get('app_add', 'BasisSet/appAddIndex'); //应用添加页
        Route::post('app/add', 'BasisSet/addApp'); //添加应用
        Route::get('app_edit', 'BasisSet/editAddIndex'); //应用编辑页
        Route::post('app/edit', 'BasisSet/editApp'); //编辑应用
        Route::post('app/del', 'BasisSet/delApp'); //删除应用
    });

    // 权限路由组
    Route::group('auth', function (){
        //管理员账户组
        Route::group('admin', function (){
            Route::get('/', 'AuthSet/admins');
            Route::get('list', 'AuthSet/adminList');
            Route::get('edit_index', 'AuthSet/adminEdit');
            Route::post('add', 'AuthSet/pulladmin');
            Route::post('edit', 'AuthSet/updateAdmin');
            Route::post('change_status', 'AuthSet/changeAdminStatus');
        });

        //角色管理组
        Route::group('roles', function (){
            Route::get('/', 'AuthSet/roles');
            Route::get('list', 'AuthSet/roleList');
            Route::get('add_index', 'AuthSet/roleAdd');
            Route::get('rule_list$', 'AuthSet/allrules');
            Route::get('rule_list/checked', 'AuthSet/rulesChecked');
            Route::post('add', 'AuthSet/addNewRole');
            Route::post('change_status', 'AuthSet/changeRoleStatus');
        });

        //权限设置组
        Route::group('access', function (){
            Route::get('/', 'AuthSet/permissions');
            Route::get('list', 'AuthSet/permissionsList');
            Route::get('add_index', 'AuthSet/authAdd');
            Route::post('mods_tree', 'AuthSet/modsTree');
            Route::post('add', 'AuthSet/pullRule');
            Route::post('change_log_status', 'AuthSet/changeLogStatus');
            Route::post('change_weight', 'AuthSet/changeWeight');
            Route::post('change_status', 'AuthSet/changeRuleStatus');
            Route::get('edit_index', 'AuthSet/authEdit');
            Route::post('edit', 'AuthSet/editRule');
        });

        //日志组
        Route::group('log', function (){
            Route::get('/', 'AuthSet/operationLog');
            Route::get('list', 'AuthSet/logList');
            Route::post('batch_del', 'AuthSet/batchDeleteLogs');
        });
    });

    // 系统设置相关路由
    Route::group('system', function (){
        // 管理用户组
        Route::group('user', function (){
            Route::get('info', 'SystemSet/userInfo'); //查看管理员信息
            Route::post('update', 'SystemSet/updateUserInfo'); //修改管理员信息

            Route::get('password/index', 'SystemSet/password'); //修改密码页面
            Route::post('password/change', 'SystemSet/changePwd'); //修改密码
        });

        // 系统通知页
        Route::get('notice', 'SystemSet/notice');
    });

})->middleware([
    app\admin\middleware\BackAuthLogin::class,
    app\admin\middleware\LogAuto::class
]);
