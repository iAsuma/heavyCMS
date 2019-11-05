axios.interceptors.response.use(response => {
    if("redirect" == response.headers['ajax-mark']){
        //从后端响应header中判断是否要跳转
        var win = window;
        while(win != win.top){
            win = win.top;
        }
        win.location.href = response.headers['redirect-path'];
    }
    return response;
})