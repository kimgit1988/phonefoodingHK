<?php
require_once('conn.php');

$paytime;
$orderSN;
$return_code;
$file_in = file_get_contents("php://input"); //接收post数据
$xml = simplexml_load_string($file_in);//转换post数据为simplexml对象
$string;
foreach($xml->children() as $child)    //遍历所有节点数据
{

$string .= $child->getName() . ": " . $child . "<br />"; //打印节点名称和节点值

if($child->getName()=="out_trade_no")    //捡取要操作的节点
{
$orderSN = $child;
}
if($child->getName()=="time_end")    //捡取要操作的节点
{
$paytime = strtotime($child);
}
if($child->getName()=="return_code")    //捡取要操作的节点
{
$return_code = $child;
}

}	
if($return_code=="SUCCESS"){
	Notify($paytime,$orderSN);
}
//file_put_contents("post.txt",$string);
//file_put_contents("get.txt",$xml);
//file_put_contents("file_in.txt",$file_in);
	
	function Notify($paytime,$orderSN){

		$error;
		$payStatus = mysql_fetch_row(mysql_query("select payStatus from mos_wx_order where orderSN='$orderSN'"));
		if($payStatus[0]==1){
			echo json_encode(array('success'=>1,'remark'=>"OK"));
			die;
		}
		if(mysql_query("UPDATE mos_wx_order SET payStatus = 1,orderStatus = 2,payTime = '$paytime' WHERE  orderSN ='$orderSN'")){
			echo json_encode(array('success'=>1,'remark'=>"OK"));
		}else{
			$error="订单主表插入失败";
			echo json_encode(array('success'=>0,'remark'=>$error));
		}
		
		die;
		}
	

?>