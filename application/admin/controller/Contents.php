<?php
namespace app\admin\controller;

use think\Request;
use Db;
use think\facade\Hook;

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
		$column = Db::table('article_column')->where('id', '<>', 5)->where('status',1)->select();
		$this->assign('column', $column);
		return $this->fetch();
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
		$count = Db::table('articles')->alias('a')->where($formWhere)->count();

		$articles = Db::table('articles')->alias('a')->leftJoin('article_column c','a.column_id=c.id')->field('a.id,a.title,a.author,a.status,c.name,FROM_UNIXTIME(a.create_time, "%Y-%m-%d %H:%i:%S") AS create_time')->where($formWhere)->page($page, $limit)->order('a.id', 'desc')->select();
	
		return table_json($articles, $count);
	}
	
	public function articlesAdd()
	{
		$column = Db::table('article_column')->where('id', '<>', 5)->where('status',1)->select();
		$this->assign('column', $column);
		return $this->fetch();
	}

	public function addArticles(Request $request)
	{
		if(checkFormToken($request->post())){
			Db::startTrans();
			try {
				$data = [
					'title' => $request->post('title'),
					'sub_title' => $request->post('sub_title'),
					'cover_imgs' => catchImg($request->post('content')),
					'content' => $request->post('content'),
					'author' => $request->post('author'),
					'column_id' => trim($request->post('column')),
					'status' => $request->post('status') ?: -2,
					'create_time' => time()
				];

				$validate = new \app\admin\validate\Articles;
	            if(!$validate->scene('articles')->check($data)){
	                exit(res_json_str(-1, $validate->getError()));
	            }

				$result = Db::table('articles') -> insert($data);

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
		$id && $info = Db::table('articles')->where(['id' => $id])->find();
		isset($info) && $this->assign('info', $info);

		$column = Db::table('article_column')->where('id', '<>', 5)->where('status',1)->select();
		$this->assign('column', $column);
		
		return $this->fetch();
	}

	public function editArticles(Request $request)
	{
		try {
			$post = $request->post();
			!checkFormToken($post) && exit(res_json_native('-2', '请勿重复提交'));

		    $data = [
				'title' => $request->post('title'),
				'sub_title' => $request->post('sub_title'),
				'cover_imgs' => catchImg($request->post('content')),
				'content' => $request->post('content'),
				'author' => $request->post('author'),
				'column_id' => trim($request->post('column')),
				'status' => $request->post('status') ?: -2
			];

			$validate = new \app\admin\validate\Articles;
            if(!$validate->scene('articles')->check($data)){
                exit(res_json_str(-1, $validate->getError()));
            }

			$result = Db::table('articles') ->where('id', (int)$post['id']) -> update($data);
			!is_numeric($result) && exit(res_json_native(-1, '修改失败'));
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
	
		if (Db::table('articles') ->where('id', 'IN', $id) -> update($data)) { 	
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
        !$res && exit(res_json_native(-3, '修改失败'));

        return res_json(1);
    }

    public function manu()
    {
    	$column = Db::table('article_column')->where('id', '=', 5)->where('status',1)->select();
		$this->assign('column', $column);
		return $this->fetch();
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
		$count = Db::table('articles')->alias('a')->where($formWhere)->count();

		$articles = Db::table('articles')->alias('a')->leftJoin('article_column c','a.column_id=c.id')->field('a.id,a.title,a.author,a.status,c.name,FROM_UNIXTIME(a.create_time, "%Y-%m-%d %H:%i:%S") AS create_time')->where($formWhere)->page($page, $limit)->order('a.id', 'desc')->select();
	
		return table_json($articles, $count);
	}

	public function articleDetail(Request $request, int $id)
	{
		if(empty($id)){
            return $this->fetch('index@public/404', ['msg' => '文章不存在']);
        }

        $data = [
            ['id', '=', $id]
        ];

        $info = Db::name('articles')->field('id,title,content,create_time')->where($data)->find();
        if(empty($info)){
            return $this->fetch('index@public/404', ['msg' => '文章不存在']);
        }

        $this->assign('info', $info);
        return $this->fetch();
	}

	/**
	 * 栏目管理
	 */
	public function column()
	{
	   return $this->fetch();
	}

	public function columnList()
	{	

		$get = $this->request->get();
		$page = $get['page'] ?? 1;
		$limit = $get['limit'] ?? 10;

		$where = [
			['status', '<>', '-1'],
			['name', 'LIKE', $get['name'] ?? '']
		];
		
		$formWhere = $this->parseWhere($where);
		$count = Db::table('article_column')->where($formWhere)->count();
		$user = Db::table('article_column')->field('id,name,status')->where($formWhere)->page($page, $limit)->order('id', 'desc')->select();
		
		return table_json($user, $count);
	}


	public function columnAdd()
	{
		return $this->fetch();
	}

	public function addColumn(Request $request)
	{
		if(checkFormToken($request->post())){
  			$validate = new \app\admin\validate\Articles;
            if(!$validate->scene('column')->check($request->post())){
                exit(res_json_str(-1, $validate->getError()));
            }

			try {
				$data = [
					'name' => $request->post('name'),
					'status' => $request->post('status') ?: -2
				];

				$result = Db::table('article_column') -> insert($data);
				!$result && exit(res_json_native(-3, '添加失败'));
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
		$id && $info = Db::table('article_column')->where(['id' => $id])->find();

		isset($info) && $this->assign('info', $info);
		
		return $this->fetch();
	}


	public function editColumn(Request $request)
	{
		try {
			$post = $request->post();
			!checkFormToken($post) && exit(res_json_native('-2', '请勿重复提交'));

			$validate = new \app\admin\validate\Articles;
            if(!$validate->scene('column')->check($request->post())){
                exit(res_json_str(-1, $validate->getError()));
            }

			$data = [
					'name' => $request->post('name'),
					'status' => $request->post('status') ?: -2
			];

			$result = Db::table('article_column')->where('id', (int)$post['id'])->update($data);
			!is_numeric($result) && exit(res_json_native(-1, '修改失败'));
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
		$data['status'] = -1 ;
	
		if (Db::table('article_column') ->where('id', 'IN', $id) -> update($data)) { 
			Hook::listen('admin_log', ['栏目管理', '删除了栏目']);		
			return res_json(1); 
		} else {
			return res_json(-1);
		}
	}


	public function changeColumnStatus()
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

        $id && $res = Db::name('article_column')->where('id', '=', $id)->update(['status' => $status]);
        !$res && exit(res_json_native(-3, '修改失败'));

        return res_json(1);
    }
}