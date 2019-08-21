<?php
	$post = $_POST;
	$url = 'http://food.soundwavemacau.com/public/api/index/getcontact.html';
	$data = [
		'number'=>isset($post['number'])?$post['number']:'',
        'token'=>isset($post['token'])?$post['token']:'',
	];
	$curl = curl($url,$data);
	$curl = json_decode($curl,true);
	if($curl['errorcode']==0){
		$return['post'] = $data;
		$return['msg'] = $curl;
	}else{
		$return['post'] = $data;
		$return['msg'] = $curl['errormsg'];
	}
	echo json_encode($return);

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
?>