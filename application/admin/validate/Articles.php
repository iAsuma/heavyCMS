<?php

namespace app\admin\validate;

use think\Validate;

class Articles extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'title' => 'require|min:2',
        'content' => 'require|min:10',
        'column_id' => 'require',
        'author' => 'require|min:2',
        'name' => 'require|min:2'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'title.require'    => '标题不能为空',
        'title.min'    => '标题名称不能低于2个字符',
        'content.require'    => '内容不能为空',
        'column_id.require'    => '栏目不能为空',
        'author.require'    => '发布人不能为空',
        'author.min'    => '发布人不能低于2个字符',
        'name.require'    => '标类别名称不能为空',
        'name.min'    => '类别名称不能低于2个字符'
    ];

      protected $scene = [
        'articles' => ['title', 'content','column_id', 'author'],
        'column' => ['name']
    ];
}
