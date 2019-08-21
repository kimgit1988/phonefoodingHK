function href_ajax(url){
    $.ajax({
        type: "POST",
        url : url,
        data: {},
        async: true,
        success: function(data) {
            if(data.code){
                alert(data.msg);
                window.location.reload();
            }else{
                alert(data.msg);
            }
        },
        error: function(request) {
            alert('頁面錯誤');
        }
    });
}