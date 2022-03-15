<?php
namespace app\admin\controller;

use think\facade\View;
use think\Request;
use think\facade\Db;
use util\Hook;

/**
 * 内容管理
 */
class Contents extends Base
{	
	/**
	 * 文章管理
	 */
	public function articles()
	{	
		$column = Db::name('article_column')->where('id', '<>', 5)->where('status',1)->select();
		View::assign('column', $column);
		return View::fetch();
	}

	public function articlesList()
	{
		
		$get = $this->request->get();
		$page = $get['page'] ?? 1;
		$limit = $get['limit'] ?? 10;

		$where = [
			['a.status', '<>', '-1'],
			['a.column_id', '<>', 5],
			['a.title', 'LIKE', $get['title'] ?? ''],
			['a.column_id', '=', $get['column'] ?? '']
		];
		
		$formWhere = $this->parseWhere($where);
		$count = Db::name('articles')->alias('a')->where($formWhere)->count();

		$articles = Db::name('articles')->alias('a')->leftJoin('article_column c','a.column_id=c.id')->field('a.id,a.title,a.author,a.status,c.name,FROM_UNIXTIME(a.create_time, "%Y-%m-%d %H:%i:%S") AS create_time')->where($formWhere)->page($page, $limit)->order('a.id', 'desc')->select();
	
		return table_json($articles, $count);
	}
	
	public function articlesAdd()
	{
		$column = Db::name('article_column')->where('id', '<>', 5)->where('status',1)->select();
		View::assign('column', $column);
		return View::fetch();
	}

	public function articlesAdd2()
	{
		$column = Db::name('article_column')->where('id', '<>', 5)->where('status',1)->select();
		View::assign('column', $column);
		return View::fetch();
	}

	public function addArticles(Request $request)
	{
		if(checkFormToken($request->post())){
			Db::startTrans();
			try {
				$data = [
					'title' => $request->post('title'),
					'sub_title' => $request->post('sub_title'),
					'cover_imgs' => catch_img($request->post('content')),
					'content' => $request->post('content'),
					'author' => $request->post('author'),
					'column_id' => trim($request->post('column')),
					'status' => $request->post('status') ?: -2,
					'create_time' => time()
				];

				$validate = new \app\admin\validate\Articles;
	            if(!$validate->scene('articles')->check($data)){
	                return res_json(-1, $validate->getError());
	            }

				$result = Db::name('articles') -> insert($data);

				if(!$result){
					Db::rollback();
					return res_json(-4, '添加失败');
				}
				
				Hook::listen('admin_log', ['文章管理', '发布了文章"'.$data['title'].'"']);
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

	public function articlesEdit()
	{
		$id = (int)$this->request->get('id');
		$id && $info = Db::name('articles')->where(['id' => $id])->find();
		isset($info) && View::assign('info', $info);

		$column = Db::name('article_column')->where('id', '<>', 5)->where('status',1)->select();
		View::assign('column', $column);
		
		return View::fetch();
	}

	public function editArticles(Request $request)
	{
		try {
			$post = $request->post();
			if(!checkFormToken($post)) return res_json('-2', '请勿重复提交');

		    $data = [
				'title' => $request->post('title'),
				'sub_title' => $request->post('sub_title'),
				'cover_imgs' => catch_img($request->post('content')),
				'content' => $request->post('content'),
				'author' => $request->post('author'),
				'column_id' => trim($request->post('column')),
				'status' => $request->post('status') ?: -2
			];

			$validate = new \app\admin\validate\Articles;
            if(!$validate->scene('articles')->check($data)){
                return res_json(-1, $validate->getError());
            }

			$result = Db::name('articles') ->where('id', (int)$post['id']) -> update($data);
			if($result === false) return res_json(-1, '修改失败');
			Hook::listen('admin_log', ['文章管理', '修改了文章"'.$data['title'].'"']);
	
			destroyFormToken($post);
			return res_json(1);
		} catch (\Exception $e) {
			return res_json(-100, $e->getMessage());
		}
		
	}

	public function del(Request $request)
	{
		
		$id = $request->post('id');
		$data['status'] = -1 ;
	
		if (Db::name('articles') ->where('id', 'IN', $id) -> update($data)) { 	
			Hook::listen('admin_log', ['文章管理', '删除了文章']);	
			return res_json(1); 
		} else {
			return res_json(-1);
		}
	  
	}

	public function changeArticlesStatus()
    {
        $id = (int)$this->request->post('id');

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

        $id && $res = Db::name('articles')->where('id', '=', $id)->update(['status' => $status]);
        if(!$res) return res_json(-3, '修改失败');

        return res_json(1);
    }

    public function manu()
    {
    	$column = Db::name('article_column')->where('id', '=', 5)->where('status',1)->select();
		View::assign('column', $column);
		return View::fetch();
    }

    public function manusList()
	{
		$get = $this->request->get();
		$page = $get['page'] ?? 1;
		$limit = $get['limit'] ?? 10;

		$where = [
			['a.status', '<>', '-1'],
			['a.column_id', 'IN', '5'],
			['a.title', 'LIKE', $get['title'] ?? ''],
			['a.column_id', '=', $get['column'] ?? ''],
			['a.status', '=', $get['status'] ?? '']
		];
		
		$formWhere = $this->parseWhere($where);
		$count = Db::name('articles')->alias('a')->where($formWhere)->count();

		$articles = Db::name('articles')->alias('a')->leftJoin('article_column c','a.column_id=c.id')->field('a.id,a.title,a.author,a.status,c.name,FROM_UNIXTIME(a.create_time, "%Y-%m-%d %H:%i:%S") AS create_time')->where($formWhere)->page($page, $limit)->order('a.id', 'desc')->select();
	
		return table_json($articles, $count);
	}

	public function articleDetail(Request $request, int $id)
	{
		if(empty($id)){
            return View::fetch('index@public/404', ['msg' => '文章不存在']);
        }

        $data = [
            ['id', '=', $id]
        ];

        $info = Db::name('articles')->field('id,title,content,create_time')->where($data)->find();
        if(empty($info)){
            return View::fetch('index@public/404', ['msg' => '文章不存在']);
        }

        View::assign('info', $info);
        return View::fetch();
	}

	/**
	 * 栏目管理
	 */
	public function column()
	{
	   return View::fetch();
	}

	public function columnList()
	{	

		$get = $this->request->get();
		$page = $get['page'] ?? 1;
		$limit = $get['limit'] ?? 10;

		$where = [
			['is_delete', '=', 0],
			['name', 'LIKE', $get['name'] ?? '']
		];
		
		$formWhere = $this->parseWhere($where);
		$count = Db::name('article_column')->where($formWhere)->count();
		$user = Db::name('article_column')->field('id,name,status')->where($formWhere)->page($page, $limit)->order('id', 'desc')->select();
		
		return table_json($user, $count);
	}

	public function columnAdd()
	{
		return View::fetch();
	}

	public function addColumn(Request $request)
	{
		if(checkFormToken($request->post())){
  			$validate = new \app\admin\validate\Articles;
            if(!$validate->scene('column')->check($request->post())){
                return res_json(-1, $validate->getError());
            }

			try {
				$data = [
					'name' => $request->post('name'),
					'status' => $request->post('status') ?: -2,
                    'create_time' => time(),
                    'create_by' => $this->uid
				];

				$result = Db::name('article_column') -> insert($data);
				if(!$result) return res_json(-3, '添加失败');
				Hook::listen('admin_log', ['栏目管理', '新增了栏目'.$data['name']]);

				destroyFormToken($request->post());
				return res_json(1);
			} catch (\Exception $e) {
				return res_json(-100,$e->getMessage());
			}
		}

		return res_json(-2, '请勿重复提交');
	}

	public function columnEdit()
	{
		$id = (int)$this->request->get('id');
		$id && $info = Db::name('article_column')->where(['id' => $id])->find();

		isset($info) && View::assign('info', $info);
		
		return View::fetch();
	}

	public function editColumn(Request $request)
	{
		try {
			$post = $request->post();
			if(!checkFormToken($post)) return res_json('-2', '请勿重复提交');

			$validate = new \app\admin\validate\Articles;
            if(!$validate->scene('column')->check($request->post())){
                return res_json(-1, $validate->getError());
            }

			$data = [
                'name' => $request->post('name'),
                'status' => $request->post('status') ?: -2,
                'update_time' => time(),
                'update_by' => $this->uid
			];

			$result = Db::name('article_column')->where('id', (int)$post['id'])->update($data);
			if($result === false) return res_json(-1, '修改失败');
			Hook::listen('admin_log', ['栏目管理', '修改了栏目'.$data['name']]);

			destroyFormToken($post);
			return res_json(1);
		} catch (\Exception $e) {
			return res_json(-100, $e->getMessage());
		}
		
	}

	public function columnDel(Request $request)
	{
		$id = $request->post('id');
		$data['is_delete'] = 1 ;
		$data['delete_date'] = date('Y-m-d H:i:s');
	
		if (Db::name('article_column') ->where('id', 'IN', $id) -> update($data)) { 
			Hook::listen('admin_log', ['栏目管理', '删除了栏目']);		
			return res_json(1); 
		} else {
			return res_json(-1);
		}
	}

	public function changeColumnStatus()
    {
        $id = (int)$this->request->post('id');

        $data = [];
        switch ($this->request->post('status')) {
            case 'true':
                $data['status'] = 1;
                $data['update_time'] = time();
                $data['update_by'] = $this->uid;
                break;
            case 'delete':
                $data['is_delete'] = 1;
                $data['delete_date'] = date('Y-m-d H:i:s');
                break;
            default:
                $data['status'] = -2;
                $data['update_time'] = time();
                $data['update_by'] = $this->uid;
                break;
        }

        $id && $res = Db::name('article_column')->where('id', '=', $id)->update($data);
        if(!$res) return res_json(-3, '修改失败');

        return res_json(1);
    }
}