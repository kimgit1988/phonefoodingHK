<?php
	use think\Db;
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

	function curl($url,$data=null){
        if (empty($url)) {
            return false;
        }
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

    //毫秒时间戳
    function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    function print_order($orderSN){
        $contact_number = session('mob_user.contact_number');
        $order = DB::name('wxOrder')->where('orderSN',$orderSN)->where('contactNumber',$contact_number)->where('isDelete',0)->find();
        $foods = Db::name('wxOrderGoods')->where('orderSN',$orderSN)->select();
        $contact_content = '1B40';
        $contact_content .= '0A1B45001B61011D2111';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$order['contactName']));
        $contact_content .= '0A1B61001D21000A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'訂單號：'.$order['orderSN']));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'微信名：'.$order['userNick']));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'餐枱號：'.$order['contactMemberName']));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'點餐號：'.$order['orderAssignedNumber']));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'時  間：'.date('Y-m-d H:i:s',$order['createTime'])));
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'菜式   單價   數量   小計'));
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        foreach($foods as $key => $val){
            $contact_content .= '0A';
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['goodsName']));
            $contact_content .= '0A';
            $totle = $val['goodsPrice']*$val['num'];
            $length_price = strlen($val['goodsPrice']);
            $length_number = strlen($val['num']);
            $length_totle = strlen($totle);
            for ($i=$length_price; $i < 11 ; $i++) { 
                $val['goodsPrice'] = ' '.$val['goodsPrice'];
            }
            for ($i=$length_number; $i < 5 ; $i++) { 
                $val['num'] = $val['num'].' ';
            }
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['goodsPrice'].'    '));
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['num']));
            $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$totle));
        }
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'合計金額：HKD$ '.$order['goodsAmount']));
        $contact_content .= '0A';
        $discount = $order['moneyPaid']-$order['moneyPaid'];
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'優惠金額：HKD$ '.$discount));
        $contact_content .= '0A';
        $contact_content .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'實收金額：HKD$ '.$order['moneyPaid']));
        $contact_content .= '0A1B61020A';
        $nick = session('mob_user.nick');
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",date('Y-m-d H:i:s').'/'.$order['contactNumber'].'/'.$nick));
        $contact_content .= '0A';
        $contact_content .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'豐富點智能點餐'));
        $contact_content .= '0A1D564200';
        $contact_print = post_print($contact_content,$orderSN);
        $food_content = array();
        $food_number = 0;
        foreach($foods as $key => $val){
            $food_content[$food_number] = '1B400A1B45001B61011D2111';
            $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'餐枱號：'.$order['contactMemberName']));
            $food_content[$food_number] .= '0A1B61001D2100';
            $food_content[$food_number] .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
            $specArr = array();
            preg_match_all('/\[.*?\]/i', $val['goodsName'],$specArr);
            if(empty($specArr[0])){
                // 无规格
                $food_content[$food_number] .= '0A1D2122';
                $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$val['goodsName'].'   '));
                $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'X'.$val['num']));
            }else{
                $spec = '';
                $name = $val['goodsName'];
                foreach ($specArr[0] as $k => $v) {
                    $name = str_replace($v,"",$name);
                    $spec .= $v;
                }
                $food_content[$food_number] .= '0A1D2122';
                $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$name.'   '));
                $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'X'.$val['num']));
                $food_content[$food_number] .= '0A1D2111';
                $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",$spec));
            }
            
            $food_content[$food_number] .= '0A1D2100';
            $food_content[$food_number] .= '2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D2D';
            $food_content[$food_number] .= '0A1B61020A';
            $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",date('Y-m-d H:i:s').'/'.$order['contactNumber'].'/'.$nick));
            $food_content[$food_number] .= '0A';
            $food_content[$food_number] .= bin2hex(iconv("UTF-8","gbk//TRANSLIT",'豐富點智能點餐'));
            $food_content[$food_number] .= '0A1D564200';
            $food_print[$food_number] = post_print($food_content[$food_number],$orderSN.'-'.$food_number);
            $food_number++;
        }
        if($contact_print){
            $return = true;
        }else{
            $return = false;
        }
        return $return;
    }
    function post_print($text,$orderSN,$mode=3,$charset=4){
        $reqTime = getMillisecond();
        //api密钥
        $apiKey='P7LO5LP5';
        //商户编码
        $memberCode='9ebaa2b0e8b44821b40ff8f709426939';
        //设备编码
        $deviceNo = '20181106018454431';
        //订单编号
        $msgNo = $orderSN;
        $securityCode = md5($memberCode.$deviceNo.$msgNo.$reqTime.$apiKey);
        $url = 'http://printerapi.mod-softs.com:7777/SmarnetWebAPI/sendMsg';
        $content['charset'] = $charset;
        $content['reqTime'] = $reqTime;
        $content['memberCode'] = $memberCode;
        $content['deviceNo'] = $deviceNo;
        $content['securityCode'] = $securityCode;
        $content['msgDetail'] = $text;
        $content['msgNo'] = $msgNo;
        $content['mode'] = $mode;
        $res = curl($url, $content);
        $res = json_decode($res,true);
        if($res['code']==0){
            $return = true;
        }else{
            $return = false;
        }
        return $return;
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

    function getGoodsSpec($good_id,$contactNo){
        $goodsSpecs = Db::name('GoodsSpec')->where('gs_disable',1)->where('contactNumber',$contactNo)->where('gs_good_id',$good_id)->select();

        $specs = Db::name('spec')->where('isDelete',0)->where('contactNumber',$contactNo)->select();

        foreach($specs as $spec){
            $speclist[$spec['id']] = $spec;
        }

        $goodSpecList = [];

        foreach($goodsSpecs as $goodsSpec){

            if(isset($goodSpecList[$goodsSpec['gs_good_id']])){

                $parentIds = array_flip(array_column($goodSpecList[$goodsSpec['gs_good_id']],'id'));

                if(array_key_exists($goodsSpec['gs_spec_pid'],$parentIds)){

                    $goodSpecList[$goodsSpec['gs_good_id']][$parentIds[$goodsSpec['gs_spec_pid']]]['_child'][]=[
                        'id'=>$goodsSpec['gs_spec_id'],
                        'price'=>$goodsSpec['gs_price'],
                        'name'=>$speclist[$goodsSpec['gs_spec_id']]['spec_name'],
                        'is_repeat'=>$goodsSpec['is_repeat'],
                        'is_default'=>$goodsSpec['is_default'],
                        'spec_enable'=>$speclist[$goodsSpec['gs_spec_id']]['spec_disable']
                    ];

                }else{
                    if(!isset($speclist[$goodsSpec['gs_spec_pid']])){
                        continue;
                    }
                    $goodSpecList[$goodsSpec['gs_good_id']][] = [
                        'id'=>$goodsSpec['gs_spec_pid'],
                        'fid'=>$goodsSpec['gs_good_id'],
                        'name'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_name'],
                        'min'=>$speclist[$goodsSpec['gs_spec_pid']]['minselect'],
                        'max'=>$speclist[$goodsSpec['gs_spec_pid']]['maxselect'],
                        'spec_order'=>$goodsSpec['gs_spec_order'],
                        'spec_enable'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_disable'],
                        '_child'=>[
                            [
                                'id'=>$goodsSpec['gs_spec_id'],
                                'price'=>$goodsSpec['gs_price'],
                                'name'=>$speclist[$goodsSpec['gs_spec_id']]['spec_name'],
                                'is_repeat'=>$goodsSpec['is_repeat'],
                                'is_default'=>$goodsSpec['is_default'],
                                'spec_enable'=>$speclist[$goodsSpec['gs_spec_id']]['spec_disable']
                            ]
                        ]
                    ];
                }
            }else{
                $goodSpecList[$goodsSpec['gs_good_id']] = [
                    [
                        'id'=>$goodsSpec['gs_spec_pid'],
                        'fid'=>$goodsSpec['gs_good_id'],
                        'name'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_name'],
                        'min'=>$speclist[$goodsSpec['gs_spec_pid']]['minselect'],
                        'max'=>$speclist[$goodsSpec['gs_spec_pid']]['maxselect'],
                        'spec_order'=>$goodsSpec['gs_spec_order'],
                        'spec_enable'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_disable'],
                        '_child'=>[
                            [
                                'id'=>$goodsSpec['gs_spec_id'],
                                'price'=>$goodsSpec['gs_price'],
                                'name'=>$speclist[$goodsSpec['gs_spec_id']]['spec_name'],
                                'is_repeat'=>$goodsSpec['is_repeat'],
                                'is_default'=>$goodsSpec['is_default'],
                                'spec_enable'=>$speclist[$goodsSpec['gs_spec_id']]['spec_disable']
                            ]
                        ]
                    ]
                ];
            }
        }
        return $goodSpecList;
    }
?>