<?php
require_once('conn.php');

// 根据传入的fun值，进入不同的api函数
//$_POST = $_GET;
if($_POST['fun']=="findCustomerAllData"){
	findCustomerAllData($_POST['customerId'],$_POST['customerMemberId']);
	
}elseif($_POST['fun']=="addOrder"){
	addOrder($_POST['json']);
	
}elseif($_POST['fun']=="findOpenid"){
	findOpenid($_POST['code']);
	
}elseif($_POST['fun']=="findOrderlist"){
	findOrderlist($_POST['openid'],$_POST['page'],$_POST['pageSize']);
	
}else{
	echo("未知参数，无法处理！");
}
die;



///////////////////////////////////以下为API方法/////////////////////////////////////////////////

/*
获取餐馆的全部信息：餐馆、当前餐桌和全部菜单
输入：
  customerId       餐厅Id
  customerMemberId 餐桌Id
  
输出：jason串：
  cantin_info  餐厅信息 {success：0/1 ；remark：失败原因}
  canzhuo_info 餐桌信息 {success：0/1 ；remark：失败原因}
  goods_info   餐厅的所有商品信息 {success：0/1 ；remark：失败原因}
*/
function findCustomerAllData($customerId ,$customerMemberId ){
	
	$customerNo = '';

	$contact = mysql_fetch_row(mysql_query("select id,name,number,CONCAT('http://".$_SERVER['SERVER_NAME']."',logoUrl) as logoUrl,CONCAT('http://".$_SERVER['SERVER_NAME']."',bgImageUrl) as bgImageUrl from mos_contact where id='".$customerId."' and disable=1 and isDelete=0"));
	if($contact[0]>0){
		$data['cantin_info']=array('customerNo'=>$contact[2],'name'=>$contact[1],'customerSuccess'=>1,'logoUrl'=>$contact[3],'bgImageUrl'=>$contact[4]);
		$customerNo = $data['cantin_info']['customerNo'];
		// echo json_encode();
	}else{
		$data['cantin_info']=array('customerSuccess'=>0);
		// echo json_encode(array('customerSuccess'=>0));
	}

	$contactC = mysql_fetch_row(mysql_query("select name,number,cCategory,cCategoryName,contactNumber,description,remark from mos_contact_member where contactnumber='".$customerNo."' and id='".$customerMemberId."' and disable=1 and isDelete=0"));
	if(!empty($contactC)){
		$data['canzhuo_info']=array('name'=>$contactC[0],'number'=>$contactC[1],'cCategory'=>$contactC[2],'cCategoryName'=>$contactC[3],'contactNumber'=>$contactC[4],'description'=>$contactC[5],'remark'=>$contactC[6],'CustomerZSuccess'=>1);
		// echo json_encode(array('name'=>$contactC[0],'number'=>$contactC[1],'cCategory'=>$contactC[2],'cCategoryName'=>$contactC[3],'contactNumber'=>$contactC[4],'description'=>$contactC[5],'remark'=>$contactC[6],'CustomerZSuccess'=>1));
	}else{
		$data['canzhuo_info']=array('CustomerZSuccess'=>0);
		// echo json_encode(array('CustomerZSuccess'=>0));
	}

	$res=mysql_query("SELECT c.id as categoryId,c.`name` AS categoryName,g.number,g.`name`,g.salePrice as price, 0 as Count, CONCAT('http://".$_SERVER['SERVER_NAME']."',g.thumbnailUrl) as icon, CONCAT('http://".$_SERVER['SERVER_NAME']."',g.imgUrl) as image FROM mos_category c LEFT JOIN mos_goods g ON c.id = g.categoryId WHERE c.typeNumber = 'trade' AND g.isDelete = 0 AND c.isDelete = 0 AND c.contactNumber = '{$customerNo}'  and g.disable=1 and g.isDelete=0 order by c.id");
	
	while($arr=mysql_fetch_assoc($res)){//取出表study_sql中的所有结果集
 		$contactS[] = $arr;
    }


    //............................
    $goodsList = array();
    $goodsObj = null;
    if(!empty($contactS)){
		foreach ($contactS as $key => $value) {
			if($goodsObj == null) {
				$goodsObj = array('name' => $value['categoryName'], 'type' => '-1', 'foods' => array());
			}
			else if($goodsObj['name'] != $value['categoryName']) {
				array_push($goodsList, $goodsObj);
				$goodsObj = array('name' => $value['categoryName'], 'type' => '-1', 'foods' => array());
			}

			array_push($goodsObj['foods'], $value);
		}
		if($goodsObj != null) {
			array_push($goodsList, $goodsObj);
		}
		$data['goods_info']=$goodsList;
	}
	else {
		$data['goods_info']=array('CustomerZSuccess'=>0);
	}
	exit(json_encode($data));
}


/*
新增订单<插入主表&子表>
输入：jason{全部订单信息插入主表&子表}
输出：jason串：{success：0/1 ；remark：失败原因}
*/
function addOrder($json){
	$goods=json_decode($json,true);//得到返回的数组
	
	$times=time();//初始化一个时间
	$Body    = "海鸥淘";//商家名称
	$OrderNo = date('YmdHis',$times).rand(1000,9999);//生成唯一订单号
	$Total_fee = $goods[1]['totalPrice'];//订单金额
	$Notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/api'.'/wxPayNotify.php';//后台通知地址

	$Openid  = $goods[0]['userId'];// Openid
	if($Openid==""){
		echo json_encode(array('success'=>0,'error'=>'Openid 为空'));die;
	}
	$arr=array(
		'userId'=>$goods[0]['userId'],
		'orderStatus'=>1,
		'payStatus'=>0,
		'payName'=>'微信支付',
		'goodsAmount'=>$goods[1]['totalPrice'],
		'moneyPaid'=>$goods[1]['totalPrice'],
		'createTime'=>$times,
		'goodslist'=>$goods[2]['carts'],
		'contactNumber'=>$goods[3]['cantingNo'],
		'contactName'=>$goods[4]['cantingName'],
		'contactLogoUrl'=>$goods[5]['cantingLogoUrl'],
		'contactMemberNumber'=>$goods[6]['canzhuoNo'],
		'contactMemberName'=>$goods[7]['canzhuoName'],
	);
	//$json=json_encode($order);
	//$arr=json_decode($json,true);//得到返回的数组
	$error;
	
	$arr[contactLogoUrl] = str_replace("http://".$_SERVER['SERVER_NAME'],"",$arr[contactLogoUrl]);
	if(!mysql_query("INSERT INTO mos_wx_order(orderSN,userId,orderStatus,payStatus,payName,goodsAmount,moneyPaid,createTime,contactNumber,contactName,contactLogoUrl,contactMemberNumber,contactMemberName) VALUES ('$OrderNo','$arr[userId]',$arr[orderStatus],$arr[payStatus],'$arr[payName]',$arr[goodsAmount],$arr[moneyPaid],'$arr[createTime]','$arr[contactNumber]','$arr[contactName]','$arr[contactLogoUrl]','$arr[contactMemberNumber]','$arr[contactMemberName]')")){
		$error="订单主表插入失败";
	}
	$i=0;
	foreach($arr['goodslist'] as $ls){
		//$customer=$ls['customerNo'];
		//$good=$ls['goodsNo'];
		$ls[icon] = str_replace("http://".$_SERVER['SERVER_NAME'],"",$ls[icon]);
		if(!mysql_query("INSERT INTO mos_wx_order_goods(orderSN,contactNumber,contactMemberNumber,goodsThumbnailUrl,goodsNumber,goodsName,num,goodsPrice) VALUES ('$OrderNo','$arr[contactNumber]','$arr[contactMemberNumber]','$ls[icon]','$ls[number]','$ls[name]',$ls[num],$ls[price])")){
			$error.="订单商品表第".$i."个商品插入失败";
		}
		$i++;
 	}
	
	if($error==""||$error==null){
		require_once('wxPay.php');
		$WxPay = new WXPay();
		$Total_fee = $Total_fee * 100;
		$parameters = $WxPay->index($Body,$OrderNo,$Total_fee,$Notify_url,$Openid); 
		$shuzu=json_decode($parameters,true);//得到返回的数组
		$paySign = $shuzu['paySign'];
		if(mysql_query("UPDATE mos_wx_order SET wxpaystring = '$parameters',paySign = '$paySign' WHERE  orderSN ='$OrderNo'" )){
			echo $parameters;
		}
		//echo json_encode(array('success'=>1,'remark'=>"OK"));
	}else{
		echo json_encode(array('success'=>0,'remark'=>$error));
	}
	die;
}

/**
获取微信openid
*/
function findOpenid($code){	

		require_once('WxpayAPI/lib/WxPay.Api.php');
		
		$openid = WxPayApi::getOpenId($code); 
		
		echo $openid;die;
}


/*
获取所有的订单列表
输入：微信openid
	  页码page
	  每页加载的数量pageSize

输出：订单jason串
*/
function findOrderlist($openid,$page,$pageSize){
	if($openid=="" || $page=="" || $pageSize==""){
		echo json_encode(array('success'=>0,'error'=>'Openid 为空'));die;
	}
	$sIndex = ($page - 1) * $pageSize;
	$mos_wx_order=mysql_query("select * from mos_wx_order where userId='".$openid."' order by id desc limit ".$sIndex.",".$pageSize."");
	$i=0;
	$arr;
	while($order=mysql_fetch_array($mos_wx_order, MYSQL_ASSOC)) 
    {
		$order['contactLogoUrl'] = "http://".$_SERVER['SERVER_NAME'].$order['contactLogoUrl'];
		
		$arr[$i] = $order;

		$arr[$i]['createTime']=date('Y-m-d H:i:s',$order['createTime']);
		
		$result=mysql_query("select orderSN, CONCAT('http://".$_SERVER['SERVER_NAME']."',goodsThumbnailUrl) as goodsThumbnailUrl, goodsName, num, goodsPrice, num * goodsPrice as numPrice from mos_wx_order_goods where orderSN ='".$order['orderSN']."'");
		
		$arr[$i]['goodlist'] = [];
		while($goodlist = mysql_fetch_array($result, MYSQL_ASSOC)) {
			array_push($arr[$i]['goodlist'], $goodlist);
		}
		
		$i++;
	}  // while

	echo json_encode(array('success'=>1,'arr'=>$arr, 'count'=>count($arr)));
	die;
}

?>