window.onload=function(){
    //禁止缩放
    document.addEventListener('touchstart', function (event) {
        if (event.touches.length > 1) {
            event.preventDefault();
        }
    }, {
        passive: false  // 关闭被动监听
    });
    var lastTouchEnd = 0;
    document.addEventListener('touchend', function (event) {
        var now = (new Date()).getTime();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, {
        passive: false  // 关闭被动监听
    });
    document.addEventListener('gesturestart', function (event) {
        event.preventDefault();
    }, {
        passive: false  // 关闭被动监听
    });
};