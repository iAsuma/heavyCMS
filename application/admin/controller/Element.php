<?php
namespace app\admin\controller;
use think\Request;
use Db;
use think\facade\Hook;
/**
 * 
 */
class Element extends Base
{
	
	public function banner()
    {
        return $this->fetch();
    }

    public function bannerList()
    {   
        $get = $this->request->get();
        $page = $get['page'] ?? 1;
        $limit = $get['limit'] ?? 10;
        
        $where = [
            ['status', '=', 1],
            ['title', 'LIKE', $get['title'] ?? '']
        ];
 
        $formWhere = $this->parseWhere($where);
        
        $count = Db::table('banners')->where($formWhere)->count();
        $data = Db::table('banners')->where($formWhere)->page($page, $limit)->order(['sorted', 'id'=>'desc' ])->select();

        return table_json($data, $count);
    }

     public function bannerAdd()
    {
        return $this->fetch();
    }

    public function addBanner(Request $request)
    {
        if(checkFormToken($request->post())){

            $validate = \think\Validate::make([
                'title' => 'require|min:2',
                'image' => 'require',
            ],[
                'title.require'=> '请填写标题',
                'title.min'    => '标题名称最少不能少于2个字符',
                'image.require'    => '请上传图片'
            ]);

            if(!$validate->check($request->post())){
                return res_json(-3, $validate->getError());
            }
    
            $image = app('upload')->base64ToImage4Banner($request->post('image'), [640, 320]);
      
            try {
                $data = [
                    'title' => $request->post('title'),
                    'landing_url' => $request->post('landing_url'),
                    'img' => $image[1],
                    'status' => 1
                ];

                $result = Db::table('banners') -> insert($data);
                !$result && exit(res_json_native(-3, '添加失败'));
                Hook::listen('admin_log', ['首页轮播图', '添加了banner']);

                destroyFormToken($request->post());
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100, $e->getMessage());
            }
        }

        return res_json(-2, '请勿重复提交');
    }

    public function bannerEdit()
    {
        $id = (int)$this->request->get('id');
        $id && $info = Db::table('banners')->where(['id' => $id])->find();
        isset($info) && $this->assign('info', $info);
        return $this->fetch();
    }


    public function editBanner(Request $request)
    {   
        if(checkFormToken($request->post())){

             $validate = \think\Validate::make([
                    'title' => 'require|min:2'
                ],[
                    'title.require'=> '请填写标题',
                    'title.min'    => '标题名称最少不能少于2个字符'
                ]);

                if(!$validate->check($request->post())){
                    return res_json(-3, $validate->getError());
                }

            try {
                $post = $request->post();
          
                $data = [
                        'title' => $request->post('title'),
                        'landing_url' => $request->post('landing_url')
                ];

                if($request->post('image')){
                    $image = app('upload')->base64ToImage4Banner($request->post('image'), [700, 350]);
                    $data['img'] = $image[1] ;
                }
                $result = Db::table('banners')->where('id', (int)$post['id'])->update($data);
                !is_numeric($result) && exit(res_json_native(-1, '修改失败'));
                Hook::listen('admin_log', ['首页轮播图', '修改了banner']);

                destroyFormToken($post);
                return res_json(1);
            } catch (\Exception $e) {
                return res_json(-100, $e->getMessage());
            }

        }

         return res_json(-2, '请勿重复提交');
        
    }

    public function bannerDel(Request $request)
    {
        
        $id = $request->post('id');
        $data = ['status' => -1];
        if (Db::table('banners') ->where('id', '=', $id) -> update($data)) { 
            Hook::listen('admin_log', ['首页轮播图', '删除了轮播图']);
            return res_json(1); 
        } else {
            return res_json(-1, '删除失败');
        }
      
    }


    public function changeWeight()
    {
        $post = $this->request->post();

        $post['id'] && $res = Db::name('banners')->where('id', '=', (int)$post['id'])->update(['sorted' => (int)$post['newVal']]);
        !$res && exit(res_json_native(-3, '修改失败'));

        return res_json(1);
    }

}