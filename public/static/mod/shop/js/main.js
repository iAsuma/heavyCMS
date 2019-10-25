(function(window, document) {
    var getRem = function() {
        if (document) {
            var html = document.documentElement;
            var hWidth = (html.getBoundingClientRect().width) * (750 / 352);
            // console.log(hWidth)
            html.style.fontSize = hWidth / 16 + "px";
            // console.log(html.style.fontSize)
        }
    };
    getRem();
    window.onresize = function() {
        getRem();
    }
})(window, document)