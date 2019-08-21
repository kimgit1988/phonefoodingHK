<?php
namespace wechat;
use think\Db;
/**
* 功能说明：
* 微信核心基类，实现AccessToken获取
* @author zwf 
* @version 1.0
* @param 
* $appId微信appid；$appSecret微信秘钥； $error错误记录
*/
class Core{
    //微信appid
    public  $appId;
    //微信秘钥
    public  $appSecret;
    //错误记录
    private $error;
    //自动回复token
    public  $token;
    //授权模式
    public  $scope ='snsapi_base';

    /**
    * 构造函数
    */
  	public function __construct($config = array()) {
        //在这里可以设置默认或者默认读取配置
        $this->appId = $config['appId'];
        $this->appSecret = $config['appSecret'];
        $this->token = isset($config['token'])?$config['token']:$this->token;
        $this->scope = isset($config['scope'])?$config['scope']:$this->scope;
  	}

    /**
    * 模拟请求
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

    /**
    * 清除AccessToken，在出现错误或冲突时候使用
    */
    public function clearAccessToken(){
        $data['token_time'] = 0;
        $data['value'] = '';
        $this->update_token($data['value'],$data['token_time']);
    }

    /**
    * 获取AccessToken
    */
  	public function getAccessToken() {
        $data = $this->check_token();
        if(empty($data)){
            $data['token_time']  = 0;
            $data['value'] = '';
        }else{
            $data = $data;
        }
  
        if($data['token_time'] < time()) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->curl($url),true);
            if (!isset($res['errcode'])) {
                //token有效期
                $data['token_time'] = time() + 6000;
                $data['value'] = $res['access_token'];
                $this->update_token($data['value'],$data['token_time']);
                $access_token = $data['value'];
            }else{
                $this->error = $res;
                return false;
            }
        } else {
            $access_token = $data['value'];
        }
        return $access_token;
    }

    public function check_token(){
      $list  = DB::name('wx_value')->where('key','access_token')->find();
      return $list;
        
    }

    public function update_token($value,$token_time){
        $data = array('value'=>$value,'token_time'=>$token_time);
        $list  = DB::name('wx_value')->where('key','access_token')->update($data);
    }

    /**
    * 获取错误记录
    */
    public function getError(){
        return  $this->error;
    }

    /**
    * 设置错误记录
    */
    public function setError($error){
        $this->error = $error;
    }
   
}
?>