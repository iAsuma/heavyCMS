layui.config({
    base: '/static/third/layuiadmin/' //静态资源所在路径
}).extend({
    index: 'lib/index' //主入口模块
}).use(['index','element', 'table'], function(){
	var form = layui.form,
	table = layui.table

	var tableInx =table.render({
	    elem: '#asuma-content'
	    ,url: $('#asuma-content').data('url') //数据接口
	    ,page: true //开启分页
	    ,limits:[10,15,20]
	    ,done:function (res, curr, count) {
	      if(count <= this.limit){
	        $('.layui-table-page').addClass('layui-hide');//总条数小于每页限制条数时隐藏分页
	      }
	    }
	    ,cols: [[ //表头
	      {field: 'id', title: 'ID',  sort: true, width:65}
	      ,{field: 'title', title: '文章标题'}
	      ,{field: 'content', title: '文章内容'}
	      ,{field: 'classify', title: '所属栏目'}
	      ,{field: 'create_time', title: '发布时间', sort: true}
	      ,{field: 'status', title: '状态', templet: '#statusTpl'}
	      ,{title: '操作', toolbar: '#optTpl'}
	    ]]
	});

	var active = {
		add: function(){
			parent.layui.index.openTabsPage($('#addOpt').data('url'), '发布文章')
	    }
	}

	$('.layui-btn.layuiadmin-btn-admin').on('click', function(){
	    var type = $(this).data('type');
	    active[type] ? active[type].call(this) : '';
	});
})