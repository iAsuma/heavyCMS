<?php
use think\facade\Route;

Route::group('', function (){
    // 商城路由组
    Route::group('shop', function (){
        Route::get('goods$', 'Shop/goods'); //商品页
        Route::get('goods/list', 'Shop/goodsList'); //商品列表
        Route::post('goods/del', 'Shop/delGoods'); //删除商品
        Route::post('goods/off_sell', 'Shop/changeSold'); //商品上下架

        Route::get('goods/create', 'Goods/create');// 商品创建页
        Route::get('goods/edit', 'Goods/edit');// 商品编辑页
        Route::post('goods/add', 'Goods/save');// 新增商品
        Route::post('goods/modify', 'Goods/modify');// 修改商品
        Route::post('goods/pictures$', 'Goods/pictures');// 商品图片显示
        Route::post('goods/pictures/list', 'Goods/pictureList');// 商品图片列表
        Route::post('goods/pictures/upload', 'Goods/uploadMainImg');// 上传图片
        Route::post('goods/pictures/delete', 'Goods/deleteGoodsPic');// 删除商品图片

        Route::get('goods/class$', 'Shop/classification'); //商品分类页
        Route::get('goods/class/list', 'Shop/classList');//商品分类列表
        Route::get('goods/class/add_index', 'Shop/classAdd');//商品分类添加页
        Route::post('goods/class/add', 'Shop/addClass');//添加商品分类
        Route::get('goods/class/edit_index', 'Shop/classEdit');//商品分类编辑页
        Route::post('goods/class/edit', 'Shop/editClass');//编辑商品分类
        Route::get('goods/class/second', 'Shop/classSecond'); //商品二级分类页
        Route::post('goods/class/edit_second', 'Shop/secondClass');//编辑商品二级分类
        Route::post('goods/class/del', 'Shop/classDel');//删除商品分类

        Route::get('sku/index', 'Shop/goodsSku'); //
        Route::get('sku/goods_set_index', 'Shop/sold'); //
        Route::post('sku/add', 'Shop/addsold'); //
        Route::get('sku/list', 'Shop/goodsSkuList'); //
        Route::post('sku/change_status', 'Shop/changeSkuSold'); //
        Route::post('sku/set_index', 'Shop/skuSet'); //
        Route::post('sku/edit', 'Shop/modifySku'); //
        Route::post('sku/del', 'Shop/skuDel'); //

        Route::get('banner$', 'Shop/banner'); //商城轮播图页面
        Route::get('banner/list', 'Shop/bannerList'); //轮播图列表
        Route::get('banner/add_index', 'Shop/bannerAdd');//轮播图添加页
        Route::post('banner/add', 'Shop/addBanner');//添加轮播图
        Route::get('banner/edit_index', 'Shop/bannerEdit');//轮播图编辑页
        Route::post('banner/edit', 'Shop/editBanner');//编辑轮播图
        Route::post('banner/del', 'Shop/bannerDel');//删除轮播图
        Route::post('banner/change_weight', 'Shop/changeWeight');//修改轮播图权重

        Route::get('reco$', 'Shop/recommended'); //
        Route::get('reco/list', 'Shop/recoList'); //
        Route::get('reco/add_index', 'Shop/recoAdd'); //
        Route::post('reco/add', 'Shop/addReco'); //
        Route::get('reco/edit_index', 'Shop/recoEdit'); //
        Route::post('reco/edit', 'Shop/editReco'); //
        Route::post('reco/change_weight', 'Shop/changeRecoWeight'); //
        Route::get('reco/detail', 'Shop/recoDetail'); //
        Route::post('reco/del', 'Shop/recoDel'); //

        Route::get('reco/goods/index', 'Shop/recogoods'); //
        Route::get('reco/goods/list', 'Shop/recogoodsList'); //
        Route::post('reco/goods/add', 'Shop/addrecogoods'); //
        Route::post('reco/goods/del', 'Shop/recoGoodsDel'); //

        Route::get('order/index', 'Order/index'); // 订单管理页面
        Route::get('order/list', 'Order/orderList'); //订单列表
        Route::get('order/detail', 'Order/detail'); //订单详情页
        Route::get('order/delivery_index', 'Order/delivery'); //订单发货页
        Route::post('order/deliver$', 'Order/makeDelivery'); //发货操作
        Route::get('order/rt_index', 'Order/returnOrder'); //退款页
        Route::get('order/rt/list', 'Order/returnOrderList'); //退款列表
        Route::get('order/rt/back_money', 'Order/backMoney'); //退款
        Route::get('order/track', 'Order/track'); //订单跟踪
        Route::get('order/reviews', 'Order/reviews'); //订单评价
        Route::post('order/recomment', 'Order/recomment'); //订单评价回复

    });
});