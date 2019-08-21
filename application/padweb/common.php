<?php
	use think\Db;
	use think\Session;
	use think\Cookie;
	use wechat\pay;
	use wechat\Oauth;
	use wechat\template;

	// 传入配置,失败-重新获取地址,授权类型
	function get_wx_info($config,$reget,$scope='snsapi_userinfo'){
		//授权类初始化
		$oauth   = new Oauth($config);
		//授权回调地址
		$url     = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		//授权
		$info = $oauth->auth($scope,$url);
		$error = $oauth->getError();
		if(!empty($error['errcode'])){
			echo "<script> alert('獲取用戶信息失敗,重新加載!');parent.location.href='".$reget."'; </script>"; die;
			header("Location:".$reget);die;
		}
		if($scope == "snsapi_userinfo"){
			//获取用户信息
			$info = $oauth->userinfo($info['access_token'], $info['openid']);
		}
		return $info;
	}

	// 餐厅编号
	function get_my_order($openid,$contactNumber="",$userType=""){
		$where = array();
		$where['userId'] = $openid;
		$where['userType'] = 1;
		if(!empty($contactNumber)){
			$where['contactNumber'] = $contactNumber;
		}
		if(!empty($userType)){
			$where['userType'] = $userType;
		}
		$order = DB::name('wx_order')->where($where)->where('isDelete',0)->select();
		if(!empty($order)){
			$orderNo = array();
			$orderlist = array();
			foreach ($order as $key => $val) {
				$orderNo[] = $val['orderSN'];
				$orderlist[$val['orderSN']] = $val;
			}
			$orderNo = implode(',', $orderNo);
			$order_goods = DB::name('wx_order_goods')->where('orderSN','in',$orderNo)->select();
			foreach ($order_goods as $k => $v) {
				$orderlist[$v['orderSN']]['_goods'][] = $v;
			}
			$order = $orderlist;
		}
		return $order;
	}

	function check_member($member,$contact="",$type1="number",$type2="contactNumber"){
		$where[$type1] = $member;
		if(!empty($contact)){
			$where[$type2] = $contact;
		}
		$res = DB::name('contact_member')->where($where)->where('disable',1)->where('isDelete',0)->find();
		$return['msg'] = $res;
		if(!empty($res)){
			$return['code'] = true;
		}else{
			$return['code'] = false;
		}
		return $return;
	}

	function check_food($foodid,$contact="",$type="contactNumber"){
		$where['id'] = ['in',$foodid];
		if(!empty($contact)){
			$where[$type] = $contact;
		}
		$res = DB::name('goods')->field('id,name,number,thumbnailUrl,salePrice,remark')->where($where)->where('disable',1)->where('isDelete',0)->select();
		$return = array();
		foreach ($res as $key => $val) {
			$return[$val['id']] = $val;
		}
		return $return;
	}
	
	function show_order($order){
		// 将结算订单转显示格式
		$order_list = array();
		$meal_list = array();
		foreach ($order as $key => $val) {
			// 非套餐
			if($val['payType']<3){
				$order_list[] = $val;
			// 套餐基本信息
			}else if($val['payType']==3){
				if(!empty($meal_list[$val['groupNumber']]['_food'])){
					$val['_food'] = $meal_list[$val['groupNumber']]['_food'];
				}
				$meal_list[$val['groupNumber']] = $val;
			// 套餐菜品
			}else{
				$meal_list[$val['groupNumber']]['_food'][] = $val;
			}
		}
		if(is_array($order_list)&&is_array($meal_list)){
			// 合并套餐非套餐
			$order_list = array_merge($order_list,$meal_list);
		}else if(is_array($meal_list)){
			$order_list = $meal_list;
		}
		return $order_list;
	}
	

	function find_order($orderSN,$type="1"){
		$order = DB::name('wx_order')->where('orderSN',$orderSN)->where('isDelete',0)->find();
		if(!empty($order)&&$type==1){
			$goods = DB::name('wx_order_goods')->where('orderSN',$orderSN)->select();
			$order['_foods'] = $goods;
		}
		return $order;
	}

	function check_contact_and_member($number,$memberNo,$userType){
		$return = array();
		if(!empty($userType)){
			$contactType = get_in_contact_type($userType);
			$where['contactType'] = ['in',$contactType];
		}
		$contact = DB::name('contact')->where($where)->where('number',$number)->where('isDelete',0)->find();
		if(empty($contact)){
	    	$return['code'] = '-1';
	    	$return['tip'] = '二維碼無效';
	    }else{
	    	if($contact['disable']!=1){
		    	$return['code'] = '-1';
		    	$return['tip'] = '二維碼無效';
	    	}else{
	    		$return['contact'] = $contact;
	    		$member = DB::name('contact_member')->where('number',$memberNo)->where('contactNumber',$number)->where('isDelete',0)->find();
	    		if(empty($member)){
			    	$return['code'] = '-1';
			    	$return['tip'] = '二維碼無效';
		    	}else{
			    	if($member['disable']!=1){
				    	$return['code'] = '-1';
				    	$return['tip'] = '二維碼無效';
		    		}else{
			    	    /*返回餐厅和桌号*/
		    			$return['member'] = $member;
		    			$return['code'] = 1;
		    			$return['contactNo'] = $number;
		    			$return['memberNo'] = $memberNo;
		    		}
		    	}
		    }
		}
		return $return;
	}

	function get_user_type(){
		$type = '';
		$array = config('user_type');
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
			//判断是不是微信
        	$type =  "wechat";
		}else if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
			//判断是不是支付宝
		    $type = "alipay";
		}else{
			$type = "default";
		}
		return $array[$type]['type'];
	}

	function user_login($type,$reget){
		$array = config('user_type');
		$user_info = array();
		// 微信登录
		if($type == $array['wechat']['type']){
			// 微信获取授权
			$wx_config = config('wx_web_config');
		    $config    = array('appId'=>$wx_config['appId'],'appSecret'=>$wx_config['appSecret'],'sign'=>$wx_config['sign']);
		    $user_info = get_wx_info($config,$reget,$wx_config['scope']);
		    if(empty($user_info['nickname'])){
                $user_info['nickname'] = '微信用戶';
            }
		    $template  = config('wx_template');
		    // $msg['first'=>'测试','key1'=>'测试','key2'=>'测试','key3'=>'测试','key4'=>'测试','remark'=>'测试'];
		    // sendTemp($user_info['openid'],$template,);
		}else if($type == $array['alipay']['type']){
			$user_info = ['openid'=>'alipayuser','nickname'=>'支付宝用户','headimgurl'=>'/mealOrderingSys/public/uploads/mobile/head/20180720/3e3fa307f2aa5f555634d5892d207108.jpg'];
		}else{
            // 测试时打开注释,正式上线前注释~
            // 匿名用户：使用客户端信息设备信息作为guestid，用来暂时区分用户
            if(empty($_COOKIE['vbus_guestid'])){
                Cookie::set('guestid',uniqid().rand(1000,9999),['prefix'=>'vbus_','expire'=>86400]);
            }
	    	$user_info = ['openid'=>Cookie::get('vbus_guestid'),'nickname'=>'普通用户','headimgurl'=>'/mealOrderingSys/public/uploads/mobile/head/20180720/3e3fa307f2aa5f555634d5892d207108.jpg'];
		}
		$user_info['user_type'] = $type;
		return $user_info;
	}

	function pay_order($orderNo,$contactNo,$method){
		//根据类型调用支付方式 1：微信 2：支付宝 3：网银支付 4：快捷支付
		switch ($method) {
			// 微信支付
			case '1':
				// 测试方式直接成功
	    		$update['orderStatus'] = 2;
	    		$update['payStatus'] = 1;
	    		$update['payTime'] = time();
	    		$order = DB::name('wx_order')->where('orderSN',$orderNo)->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
	    		$return = ['code'=>1,'msg'=>'支付成功!'];
	    		return $return;
	    		// 真正的微信支付
				// $config = config('wx_web_config');
				// $order  = find_order($orderNo,2);
				// $user   = Session::get($config['session_name']);
				// $check_contact = check_contact($contactNo);
				// if($check_contact){
				// 	$contact = $check_contact['msg'];
				// 	$return = wx_pay($config,$order['moneyPaid'],$contact['name'],$orderNo,$user['openid']);
				// 	$res = DB::name('wx_order')->where('orderSN',$contactNo)->update(['wxpaystring'=>json_encode($return['msg'])]);
				// }else{
				// 	$return['msg']  = '餐厅信息错误';
				// 	$return['code'] = 0;
				// }
				// return $return;
				break;
			// 支付宝支付
			case '2':
	    		//支付成功
	    		$update['orderStatus'] = 2;
	    		$update['payStatus'] = 1;
	    		$update['payTime'] = time();
	    		$order = DB::name('wx_order')->where('orderSN',$orderNo)->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
	    		$return = ['code'=>1,'msg'=>'支付成功!'];
	    		return $return;
				break;
			// 网银支付
			case '3':
	    		//支付成功
	    		$update['orderStatus'] = 2;
	    		$update['payStatus'] = 1;
	    		$update['payTime'] = time();
	    		$order = DB::name('wx_order')->where('orderSN',$orderNo)->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
	    		$return = ['code'=>1,'msg'=>'支付成功!'];
	    		return $return;
				break;
			// 快捷支付
			case '4':
	    		//支付成功
	    		$update['orderStatus'] = 2;
	    		$update['payStatus'] = 1;
	    		$update['payTime'] = time();
	    		$order = DB::name('wx_order')->where('orderSN',$orderNo)->where('orderStatus',1)->where('payStatus',0)->where('isDelete',0)->update($update);
	    		$return = ['code'=>1,'msg'=>'支付成功!'];
	    		return $return;
				break;
			// 未知方式
			default:
	    		//支付失败
				$return = ['code'=>0,'msg'=>'請選擇有效的支付方式!'];
	    		return $return;
				break;
		}
	}

	function wx_pay($config,$price,$name,$orderNo,$openid){
		$pay = new pay($config);
		$address = url('index/wxNotify');
		$notify = 'http://'.$_SERVER['HTTP_HOST'].$address;
		$info = array(
			'body'=>$name.'-订单'.$orderNo,
            'out_trade_no'=> $orderNo,
            'total_fee'=>$price*100,
            'notify_url'=>$notify,
            'spbill_create_ip'=> getIp(),
            'trade_type'=>'JSAPI',
            'openid'=>$openid,   
        );
        $unifiedOrder = $pay->unifiedOrder($info);
        if($unifiedOrder['return_code']!='SUCCESS'){
          	$return['code'] = 0;
			$return['msg'] = '網絡錯誤，請稍後再試！';
			return $return;
        }else if(isset($unifiedOrder['err_code'])&&$unifiedOrder['err_code']=='INVALID_REQUEST'){
        	// $unifiedOrder['prepay_id'] =  $order['prepay_id'];
        }else if(isset($unifiedOrder['prepay_id'])){
          // update_pay($unifiedOrder['prepay_id'],$ordernum);
        }
        $unifiedOrder = $pay->getParameters($unifiedOrder);
        $return['msg'] = $unifiedOrder;
        $return['code'] = 2;
        return $return;
	}
	function getIp() 
	{
	    //strcasecmp 比较两个字符，不区分大小写。返回0，>0，<0。
	    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
	        $ip = getenv('HTTP_CLIENT_IP');
	    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
	        $ip = getenv('HTTP_X_FORWARDED_FOR');
	    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
	        $ip = getenv('REMOTE_ADDR');
	    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
	        $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    $res =  preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
	    return $res;
	        
	}

	function sendTemp($openid,$template,$msg){
    	$wx_config = config('wx_web_config');
    	$data = array(
		    "touser"=>$openid,
		    "template_id"=>$template['id'],
		    "url"=>$template['url'],      
		    "data" =>  array(
		        "first" =>  array(
		            "value"=>$msg['first'],
		        ),
		        "keyword1" => array(
		            "value"=>$msg['key1'],
		        ),
		        "keyword2" => array(
		            "value"=>$msg['key2'],
		        ),
		        "keyword3" => array(
		            "value"=>$msg['key3'],
		        ),
		        "keyword4" => array(
		            "value"=>$msg['key4'],
		        ),
		        "remark" => array(
		            "value"=>$msg['remark'],
		        )
		    )
		);
		$template = new Template($wx_config);
		// file_put_contents('test01.txt', json_encode($template,JSON_UNESCAPED_UNICODE));
		$message = $template->sendTemplate($data);
		if($message){
			return true;
		}
    }

    function get_ad($position){
    	$time = time();
    	$ad = DB::name('Ad')->where('adStart','<=',$time)
            ->where('adEnd','>=',$time)
            ->where('adPosition',$position)
            ->where('disable',1)
            ->where('isDelete',0)
            ->select();
    	$piclist = array();
    	foreach ($ad as $key => $val) {
    		$url = json_decode($val['adUrl'],true);
    		foreach ($url as $k => $v) {
    			if(isset($v['sort'])){
    				$sort = $v['sort'];
    			}else{
    				$sort = 0;
    			}
    			$piclist[] = ['path'=>$v['path'],'sort'=>$sort,'jump'=>$val['adLink'],'id'=>$val['id'],'name'=>$val['adName']];
    		}
    	}
    	if(!empty($piclist)){
    		$piclist = my_sort($piclist,'sort',SORT_ASC,SORT_REGULAR);
    	}
    	return $piclist;
    }

    function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){  
        if(is_array($arrays)){  
            foreach ($arrays as $array){  
                if(is_array($array)){  
                    $key_arrays[] = $array[$sort_key];  
                }else{  
                    return false;  
                }  
            }  
        }else{  
            return false;  
        } 
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);  
        return $arrays;  
    } 
	function uploadPic($file,$path){
        $return = array();
		if($file){
			// 读取根路径配置
			$root_path = config('Rootpath');
			// 保持文件名字TP自动添加名字
			$info = $file->move(ROOT_PATH.'public'.DS.$path);
            if ($info) {
                $return['code'] = 1;
                // 获取文件路径及名称返回
                $return['msg'] = $root_path.'/'.$path.'/'.date("Ymd")."/".$info->getFilename();
                return $return;
            } else {
                // 上传失败获取错误信息
                $return['code'] = 0;
                $return['msg']  = $this->getError();
                return $return;
            }
		}else{
			$return['code'] = 0;
			$return['msg']  = '未找到圖片';
			return $return;
		}
	}
?>