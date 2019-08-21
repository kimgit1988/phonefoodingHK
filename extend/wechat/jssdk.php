<?php
namespace wechat;
use think\Db;
/**
* 功能说明：jssdk基本参数生成
* @author zwf 
* @version 1.0
*/
use wechat\core;
class Jssdk extends Core{
    //微信appid
    public  $appId;
      //微信秘钥
    public  $appSecret;
    //错误记录
    private $error;

    /**
    * 构造函数
    * @author 郑伟锋
    */
    public function __construct($config = array()) {
      //在这里可以设置默认或者默认读取配置
        // echo json_encode($config);
        $this->appId = $config['appId'];
        $this->appSecret = $config['appSecret'];
      
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
    public function newGetTicket(){

        
        $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->getAccessToken()."&type=jsapi";
        
        $res = json_decode($this->curl($url,$data),true);
    
        if($res['errcode']==0){
            $data['token_time'] = time() + 6000;
            
            $data['value'] = $res['ticket'];
        
            $this->add_ticket($data['value'],$data['token_time']);
            $ticket = $res['ticket'];
        }else{
            $this->setError($res);
            return false;
        }
       
        return $ticket;
    }
    public function getTicket(){

        $data = $this->check_ticket();
        if(empty($data)){
            $data['token_time']  = 0;
            $data['value'] = '';
        }else{
            $data = $data;  
         }
        
        if ($data['token_time'] < time()) {
            $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->getAccessToken()."&type=jsapi";
            $res = json_decode($this->curl($url,$data),true);
            if($res['errcode']==0){
                $data['token_time'] = time() + 6000;
                $data['value'] = $res['ticket'];
                $this->add_ticket($data['value'],$data['token_time']);
                $ticket = $res['ticket'];
            }else{
                $this->setError($res);
                return false;
            }
        }else{
            $ticket = $data['value'];
        }
        return $ticket;
    }
    /**
    * 生成随机数
    * @author 郑伟锋
    */
    public function createNonceStr($length = 16) {
      $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      $str = "";
      for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
      }
      return $str;
    }
    /**
    * 获取生成签名数据
    * @author 郑伟锋
    */
    public function sign($url){
        $noncestr  = $this->createNonceStr();
        $timestamp = time();
        $jsapi_ticket = $this->getTicket();
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
       // $url = "$protocol$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
        //顺序不能变
        $string="jsapi_ticket=".$jsapi_ticket."&noncestr=".$noncestr."&timestamp=".$timestamp."&url=".$url;
        $signature=sha1($string);
        $data = array("noncestr"=>$noncestr,
            "timestamp"=>$timestamp,
            "signature"=>$signature,
            "appId"=>$this->appId,
            "jsapi_ticket"=>$jsapi_ticket,
            "url"=>$url,
            "string"=>$string,
        );

        return $data;
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

  public function check_ticket(){
        
        $list  = DB::name('wx_value')->where('key','ticket')->find();
        return $list;

    }
    
    public function add_ticket($ticket,$token_time){
        
        $data = array('value'=>$ticket,'token_time'=>$token_time);
        $list  = DB::name('wx_value')->where('key','ticket')->update($data);
        // $GLOBALS['redis']->set('ticket'.$this->appId,$data);
        // $GLOBALS['redis']->expire('ticket'.$this->appId,6000);
        
    }
    
    // public function getAccessToken() {

    //     $data = $this->check_token();
          
    //     if(empty($data)){
    //         $data['token_time']  = 0;
    //         $data['token_value'] = '';
    //     }else{
    //         $data = $data;  
    //     }
    
    //     if($data['token_time'] < time()) {
    //         $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
    //         $res = json_decode($this->curl($url),true);
    //         if (!isset($res['errcode'])) {
    //             //token有效期
    //             $data['token_time'] = time() + 6000;
    //             //token
    //             $data['token_value'] = $res['access_token'];
    //             $this->add_token($data['token_value'],$data['token_time']);
    //             $access_token = $data['token_value'];
    //         }else{
    //             $this->error = $res;
    //             return false;
    //         }
    //     } else {
    //          $access_token = $data['token_value'];
    //     }
          
    //     return $access_token;
    // }
    
 


    // public function check_token(){  
    //     $list  = $GLOBALS['redis']->get($this->appId);
    //     return $list;
      
    // }
    // public function add_token($token_value,$token_time){
    //     $data = array('token_value'=>$token_value,'token_time'=>$token_time);
    //     $GLOBALS['redis']->set("token".$this->appId,$data);
    //     $GLOBALS['redis']->expire("token".$this->appId,6000);
         
    // }
    
}



// require_once('core.php');
// class Jssdk extends Core{
	  
//   	/**
//     * 构造函数
//     * @author 郑伟锋
//     */
//     public function __construct($config = array()) {
//         //默认为zwf123456，实际根据用户填写
//         parent::__construct($config);
//     }
//       /**
//     * 清除Ticket，在出现错误或冲突时候使用
//     * @author 郑伟锋
//     */
//     public function clearTicket(){
//       $data['expire_time'] = 0;
//       $data['ticket'] = '';
//       Cache::set($this->jssdkCacheTicket.$this->appId,$data);
//     }
//     /**
//     * 获取ticket
//     * @author 郑伟锋
//     */
//     public function getTicket(){
		
//         $data = unserialize(Cache::get($this->jssdkCacheTicket.$this->appId));
//         if(!isset($data['expire_time'])){
//             $data['expire_time']  = 0;
//             $data['ticket'] = '';
//         }
//         if ($data['expire_time'] < time()) {
//             $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->getAccessToken()."&type=jsapi";
//             $res = json_decode($this->curl($url,$data),true);
//             if($res['errcode']==0){
//                 //token有效期
//                 $data['expire_time'] = time() + 7000;
//                 //token
//                 $data['ticket'] = $res['ticket'];
//                 //写缓存
//                 Cache::set($this->jssdkCacheTicket.$this->appId,$data);
//                 $ticket = $res['ticket'];
//             }else{
//                 $this->setError($res);
//                 return false;
//             }
//         }else{
//             $ticket = $data['ticket'];
//         }
//         return $ticket;
//     }
//     /**
//     * 生成随机数
//     * @author 郑伟锋
//     */
//     public function createNonceStr($length = 16) {
//       $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
//       $str = "";
//       for ($i = 0; $i < $length; $i++) {
//         $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
//       }
//       return $str;
//     }
//     /**
//     * 获取生成签名数据
//     * @author 郑伟锋
//     */
//     public function sign(){
//         $noncestr  = $this->createNonceStr();
//         $timestamp = time();
//         $jsapi_ticket = $this->getTicket();
//         $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
//         $url = "$protocol$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
//         //顺序不能变
//         $string="jsapi_ticket=".$jsapi_ticket."&noncestr=".$noncestr."&timestamp=".$timestamp."&url=".$url;
//         $signature=sha1($string);
//         $data = array("noncestr"=>$noncestr,
//             "timestamp"=>$timestamp,
//             "signature"=>$signature,
//             "appId"=>$this->appId,
//             "jsapi_ticket"=>$jsapi_ticket,
//             "url"=>$url,
//             "string"=>$string,
//         );

//         return $data;
//     }
    

// }
?>