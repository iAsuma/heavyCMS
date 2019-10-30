 $(function() {
    setInterval(function() {
        var time = new Date();
        var hour = time.getHours();
        var fen = time.getMinutes()
        var miao = time.getSeconds()
        if (hour < 10) {
            $("#shi").text("0" + hour)
        } else {
            $("#shi").text(hour)
        }
        if (fen < 10) {
            $("#fen").text("0" + fen)
        } else {
            $("#fen").text(fen)
        }
        if (miao < 10) {
            $("#miao").text("0" + miao)
        } else {
            $("#miao").text(miao)
        }

    }, 1000)
})