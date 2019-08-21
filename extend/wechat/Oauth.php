<?php
namespace wechat;
use think\Request;
use wechat\core;
class Oauth{
    //微信appid
    public  $appId;
    //微信秘钥
    public  $appSecret;
    //错误记录
    private $error;
  
    public  $sign;
    //授权模式
    public  $scope ='snsapi_base';
    /**
    * 构造函数
    * @author 郑伟锋
    */
    public function __construct($config = array()) {
      //在这里可以设置默认或者默认读取配置
        $this->sign  = $config['sign'];
        $this->appId = $config['appId'];
        $this->appSecret = $config['appSecret'];
        $this->scope = isset($config['scope'])?$config['scope']:$this->scope;
    }
   
    /**
    * 模拟请求
    * @author 郑伟锋
    */
    public function curl($url,$data=null){

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
        curl_close($curl);
        return $output;

    }
    public function auth($scope="snsapi_base",$redirect_url='',$state="STATE",$lang="zh_CN"){
        $code = input('get.code');
        // $code = $_GET['code'];
        if(empty($code)){
            //获取code
          header("Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid=$this->appId&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=$scope&state=STATE#wechat_redirect");
          exit();
        }
        //获取access_token
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=$this->appId&secret=$this->appSecret&code=$code&grant_type=authorization_code";
        $res = json_decode($this->curl($url),true);
        //隐式授权，只获取openid
    if(isset($res['errcode'])){
      $this->setError($res);
      return false;
    }else{
      return $res;
    }
     
    }
  
  public function userinfo($access_token,$openid,$state="STATE",$lang="zh_CN"){
      //获取用户信息
    $url="https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=".$lang;
    $resl = json_decode($this->curl($url),true);
    if(isset($resl['errcode'])){
      $this->setError($resl);
      return false;
    }else{
      return $resl;
    }
  }
    public function getAccessToken() {
      $data = $this->check_token();
      
      if(empty($data)){
        $data['token_time']  = 0;
        $data['token_value'] = '';
      }else{
        $data = $data;
      }
  
      if($data['token_time'] < time()) {
          $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
          $res = json_decode($this->curl($url),true);
          if (!isset($res['errcode'])) {
            //token有效期
            $data['token_time'] = time() + 6000;
            //token
            $data['token_value'] = $res['access_token'];
         

            $this->add_token($data['token_value'],$data['token_time']);
            $this->update_token($data['token_value'],$data['token_time']);
            $access_token = $data['token_value'];
          }else{
            $this->error = $res;
            return false;
          }
      } else {
         $access_token = $data['token_value'];
      }
      
      return $access_token;
    }
  
 

    /**
    * 获取错误记录
    * @author 郑伟锋
    */
    public function getError(){
      return  $this->error;
    }
    /**
    * 设置错误记录
    * @author 郑伟锋
    */
    public function setError($error){
       $this->error = $error;
    }

  public function check_token(){
    $list = $GLOBALS['redis']->get($this->sign);
    return $list;
    // $sql ="select * from ".$GLOBALS['db']->DB_PREFIX."_use_token order by id desc limit 1";
    // $list =$GLOBALS['db']->one($sql);
    // return $list;
      
  }
  
  public function add_token($token_value,$token_time){
    $data = array('token_value'=>$token_value,'token_time'=>$token_time);
  
    $GLOBALS['redis']->set($this->sign,$data);
    // $sql ="insert into ".$GLOBALS['db']->DB_PREFIX."_token_list (`token_value`,`token_time`) values ('".$token_value."',".$token_time.")";
    // $GLOBALS['db']->dml($sql);
  
  }
  public function update_token($token_value,$token_time){
    $data = array('token_value'=>$token_value,'token_time'=>$token_time);
    $GLOBALS['redis']->set($this->sign,$data);
    // $sql ="update ".$GLOBALS['db']->DB_PREFIX."_use_token set `token_value` = '".$token_value."',`token_time` = '".$token_time."' where `token_id` = 1";
    // $GLOBALS['db']->dml($sql);
  }
  
    
}


  


?>