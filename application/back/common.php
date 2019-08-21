<?php
	use think\Db;
	function uploadPic($file,$path){
        $return = array();
		if($file){
			// 读取根路径配置
			$root_path = config('Rootpath');
			// 保持文件名字TP自动添加名字
			$info = $file->move(ROOT_PATH.'public'.'/'.$path);
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
			$return['msg']  = '未找到图片';
			return $return;
		}
	}

	function addContactNumber($prefix='Test'){
		$number = $prefix.date('YmdHis').rand(10,99);
		return $number;
	}

	//形成树状格式 $tree是需要树化的数组 id为标识符 pid为父级标识符 $rootid为顶级id 
	function arrtree($tree ,$child="child" ,$id="id" ,$pid="parentId" ,$rootId = 0 ,$level=1) {
		// 子集名 id 父级id 顶级id可设置
	    $return = array();  
	    foreach($tree as $leaf) {
	        if($leaf[$pid] == $rootId) {
	            $leaf["level"] = $level;
	            foreach($tree as $subleaf) {  
	                if($subleaf[$pid] == $leaf[$id]) {
	                    $leaf[$child] = arrtree($tree,$child,$id,$pid,$leaf[$id],$level+1);  
	                    break;  
	                }  
	            } 
	            $return[] = $leaf; 
	        } 
	    } 
	    return $return;  
	}

	// 生成树状option $child为数组 $childfield为子集字段 selectid为选中ID
	function getChildOption($child,$childfield="child",$selectid="",$value="id",$name="name",$level="1"){
		// 一级子集名字前缀
		$prefix = "┗━";
		$str = "";
		// 根据子集层数加长前缀
		for ($i=1; $i < $level; $i++) { 
			$prefix .="━━";
		}
		foreach ($child as $key => $val) {
			// 选中判断
			if($val[$value]==$selectid){
				$str .= "<option value=".$val[$value]." selected='selected'>".$prefix.$val[$name]."</option>";	
			}else{
				$str .= "<option value=".$val[$value].">".$prefix.$val[$name]."</option>";
			}
			// 有子集则传递子集调用自身
			if(isset($val[$childfield])){
				$str .= getChildOption($val[$childfield],$childfield,$selectid,$value,$name,$level+1);
			}
			
		}
		return $str;
	}
	function forTableStr($data){
		$str = '';
		foreach($data as $key =>$vo){
		$str .= '<tr class="news"><td>'.$vo['id'].'</td><td>'.$vo['orderSN'].'</td><td>'.$vo['contactName'].'</td><td>'.$vo['moneyPaid'].'</td><td>'.$vo['contactMemberName'].'</td><td>'.$vo['goodsAmount'].'</td><td>'.$vo['orderStatus'].'</td><td>'.$vo['payStatus'].'</td><td>'.$vo['payName'].'</td><td>'.$vo['createTime'].'</td><td>'.$vo['payTime'].'</td>';
			if($vo['orderStatus']=='已下单'){
				$str .= '<td><a class="btn btn-xs btn-default" href="'.url('Order/confirm',['id'=>$vo['id'],'action'=>2]).'">确认订单</a></td>';
				$str .= '<td><a class="btn btn-xs btn-default delete" href="'.url('Order/cancel',['id'=>$vo['id']]).'">取消订单</a>';
			}else if($vo['orderStatus']=='已确认'){
				$str .= '<td><a class="btn btn-xs btn-default" href="'.url('Order/confirm',['id'=>$vo['id'],'action'=>3]).'">完成订单</a></td><td></td>';
			}else{
				$str .= '<td></td><td></td>';
			}
			
			$str .= '</td><td><a class="btn btn-xs btn-default" href="'.url('Order/detail',['id'=>$vo['id']]).'">详情</a></td></tr>';
		}
		return $str;
	}

	function getAccessToken(){
		$res = DB::name('wx_value')->where('key','access_token')->where('token_time','>=',time())->find();
		if(!empty($res)){
			$access_token = $res['value'];
		}else{
			$appid = config('wx_config.appid');
			$secret = config('wx_config.secret');
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
			$accessToken = curl($url);
			$accessToken = json_decode($accessToken,true);
			if (!isset($accessToken['errcode'])) {
	            $access_token = $accessToken['access_token'];
	            $save['value'] = $accessToken['access_token'];
	            $save['token_time'] = time()+6000;
	            $res = DB::name('wx_value')->where('key','access_token')->update($save);
	        }
		}
		return $access_token;
	}

	function curl($url,$data=null){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if(!empty($data)){
            curl_setopt($curl,CURLOPT_POST,1);
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        if (curl_errno($curl)) {
            return false;
        }else{
            return $output;
        	curl_close($curl);
        }
    }

    //获得二维码
    function get_qrcode($access_token,$scene,$page){
    	//二维码保存的路径
        $path = ROOT_PATH.'public'.'/'.'uploads'.'/'.'qrcode'.'/'.date('Ymd').DS;
        //文件名
        $name = md5(time().rand(1000,9999)).'.jpg';
        // 读取根路径配置
		$root_path = config('Rootpath');
        // header('content-type:image/gif');
        //header('content-type:image/png');格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        header('content-type:image/jpg');
        $uid = 6;
        $data = array();
        $data['scene'] = $scene;
        $data['page'] = $page;
        $data = json_encode($data);
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $da = api_notice_increment($url,$data);
        $res = json_decode($da,true);
        if(empty($res['errcode'])){
        	// 判断路径是否存在
        	if(!is_dir($path)){
			    mkdirs($path);
			}
        	file_put_contents($path.$name, $da);
        	$return['code'] = 1;
        	$return['msg']  = '生成二维码成功';
        	$return['path'] = $root_path.'/'.'uploads'.'/'.'qrcode'.'/'.date("Ymd").'/'.$name;
        }else{
        	$return['code'] = 0;
        	$return['msg']  = '生成二维码失败';
        	$return['number'] = $res['errcode'];
        }
        return $return;
    }

    function api_notice_increment($url, $data){
        $ch = curl_init();
        $header[] = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }else{
            return $tmpInfo;
        	curl_close($ch);
        }
    }

    function add_qrcode($tableid,$memberid){
        $accesstoken = getAccessToken();
        $token = $accesstoken;
        $scene = $tableid.'-'.$memberid;    //要传的参数
        $page = 'pages/index/index';          //跳转的路径(不填默认首页)
        $return = get_qrcode($token,$scene,$page);
        return $return;
    }

    function get_banknumber_money($bn_id,$income,$contact,$market,$paymethod){
    	$bn_paymethod = array();
    	$bn_money = 0;
    	if(!empty($paymethod[$bn_id])){
    		$bn_paymethod = $paymethod[$bn_id];
    	}
    	foreach ($income as $key => $val) {
    		if(in_array($key, $bn_paymethod)){
				$bn_money += $val;
    		}
    	}
    	if(!empty($contact[$bn_id])){
			$bn_money -= $contact[$bn_id];
    	}
    	if(!empty($market[$bn_id])){
			$bn_money -= $market[$bn_id];
    	}
    	return $bn_money;
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

?>