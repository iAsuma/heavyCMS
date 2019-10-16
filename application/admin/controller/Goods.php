<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\facade\Hook;
/**
 * 商品创建与编辑
 */
class Goods extends Base
{
	
	public function create()
	{
		$classifyArr = Db::table('shop_classification')->field('id,name,pid')->select();

		$tree = new \util\Tree($classifyArr);
		$classify = $tree->leaf();
		
		$this->assign('classify', $classify);
		return $this->fetch();
	}

	public function save(Request $request)
	{
		dump($request->post());die;

		if(checkFormToken($request->post())){
            // $validate = new \app\admin\validate\Register;
            // if(!$validate->scene('register')->check($request->post())){
            //     exit(res_json_str(-1, $validate->getError()));
            // }

            Db::startTrans();
            try {
                $data = [
                    'goods_name' => $request->post('goods_name'),
                    'classification_id' => $request->post('classification'),
                    'goods_sku_attributes' => '',
                    'introduction' => $request->post('introduction'),
                    'create_time' => time(),
                    'is_sold' => '',
                    'goods_imgs' => '',
                    'description' => $request->post('description'),
                    'status' => 1,
                    'post_type' => $request->post('post_type'),
                    'freight' => $request->post('post_type') == 2 ? $request->post('freight') : 0
                ];

                $new_id = Db::name('shop_goods') -> insertGetId($data);
                !$new_id && exit(res_json_native(-6, '添加失败'));

                // $result = Db::name('auth_group_access')->insertAll($access);

                // if(!$result){
                //     Db::rollback();
                //     return res_json(-4, '添加失败');
                // }

                Hook::listen('admin_log', ['商品管理', '添加了新商品']);
                
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
}