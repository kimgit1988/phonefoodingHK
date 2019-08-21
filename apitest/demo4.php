<?php
$post = $_POST;
$url = 'http://food.soundwavemacau.com/public/api/index/postorder.html';
$order = array();
foreach ($post['food'] as $id => $val) {
    if(!empty($val['checked'])){
        $order[] = [
            'id' => $id,
            'type' => 1,
            'counter' => $val['number'],
        ];
    }
}
foreach ($post['foodws'] as $id => $val) {
    if(!empty($val['checked'])){
        $order[] = [
            'id' => $id,
            'type' => 2,
            'counter' => $val['number'],
            'specIds'=>'2,6',
            'weight'=>1,
        ];
    }
}
foreach ($post['meal'] as $id => $val) {
    if(!empty($val['checked'])){
        $order[] = [
            'id' => 11,
            'type' => 3,
            'counter' => $val['number'],
            'foods'=>[
                ['id'=>348,'cid'=>17,'type'=>5,'counter'=>$val['number'],'specIds'=>'2,6','weight'=>1]
            ]
        ];
    }
}
$data = [
    '_food'=>json_encode($order),
    'contactNo'=>isset($post['contactNo'])?$post['contactNo']:'',
    'contactMemberNo'=>isset($post['contactMemberNo'])?$post['contactMemberNo']:'',
    'openId'=>isset($post['openId'])?$post['openId']:'',
    'nickName'=>isset($post['nickName'])?$post['nickName']:'',
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