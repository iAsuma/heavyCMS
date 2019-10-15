function msg_tip(msg){
    layer.open({
        content: msg
        ,skin: 'msg'
        ,time: 2 //2秒后自动关闭
    });
}

$.fn.pictureHandle = function(callback) {
    var _upFile = $(this)[0];
    var status = false;
    var tip = '';

    _upFile.addEventListener('change',function(){
        console.log(_upFile.files)
        if (_upFile.files.length === 0) {  
            msg_tip("请选择图片");
            return; }  
        var oFile = _upFile.files[0]; 
        //if (!rFilter.test(oFile.type)) { alert("You must select a valid image file!"); return; }  
      
        /*  if(oFile.size>5*1024*1024){  
         message(myCache.par.lang,{cn:"照片上传：文件不能超过5MB!请使用容量更小的照片。",en:"证书上传：文件不能超过100K!"})  
         
         return;  
         }*/  
        if(!new RegExp("(jpg|jpeg|png)+","gi").test(oFile.type)){  
            if (typeof callback == 'function') callback({status: false, tip: "图片上传：文件类型必须是JPG、JPEG、PNG", src: ''});
            return false;
        }
        
        var reader = new FileReader();  
        reader.onload = function(e) {  
            var base64Img= e.target.result;
            //--执行resize。  
            var _ir=ImageResizer({  
                resizeMode:"auto"  
                ,dataSource:base64Img  
                ,dataSourceType:"base64"  
                ,maxWidth:1200 //允许的最大宽度  
                ,maxHeight:1200 //允许的最大高度。  
                ,onTmpImgGenerate:function(img){  

                }  
                ,success:function(resizeImgBase64,canvas){
                    //压缩后预览
                    //$("#nextview").attr("src",resizeImgBase64); 
                    status = true;
                    tip = 'Correct!';

                    if (typeof callback == 'function') callback({status: status, tip: tip, src: resizeImgBase64});
                }  
                ,debug:false
            });  

        };  
        reader.readAsDataURL(oFile);  
  
    },false);
}