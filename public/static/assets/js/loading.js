function openloading(url){
	var str = '<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" id="load">  <div class="modal-dialog modal-sm" role="document" style="position: absolute;top: 50%;left: 50%; transition: translate(-50%,-50%); -webkit-transform: translate(-50%,-50%);margin:auto;text-align:center;"><img style="width:30px;height:30px;" src="'+url+'"></div></div>';
	$('body').append(str);
	$(".bs-example-modal-sm").modal({backdrop: 'static', keyboard: false});
	$(".bs-example-modal-sm").modal('show');
}
function closeloading(){
	$(".bs-example-modal-sm").modal('hide');
	$("#load").remove();
	$(".modal-backdrop").remove();
	$('body').removeClass('modal-open');
} 
