function layer_msg(msg) {
	var index = layer.open({
		content: msg
		,className: 'layer-msg'
		,shade: false
		,time: 3
	});

	return index;
}

function layer_loading(msg) {
	var index = layer.open({
	    type: 2
	    ,content: msg
	    ,shadeClose: false
	});

	return index;
}

function layer_confirm(msg, callback, btn = ['确定', '取消']) {
	var index = layer.open({
	    content: msg
	    ,btn: btn
	    ,className: 'layer-confirm'
	    ,yes: function(idx){
	      if(typeof callback == 'function'){
	      	callback(idx)
	      }else{
	      	layer.close(idx)
	      }
	    }
	 });

	return index;
}