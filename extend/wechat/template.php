<?php
namespace wechat;
use wechat\core;
/**
* 功能说明：模板消息
* @author ki
* @version 1.0
*/
class Template extends Core{
      
    /**
    * 构造函数
    * @author ki
    */
    public function __construct($config = array()) {
        parent::__construct($config);
    }

    /**
    * 发送模板消息
    * @author ki
    */
    public function sendTemplate($data){
        // file_put_contents('test110.txt','123');
        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$this->getAccessToken();
        $res = json_decode($this->curl($url,$data),true);
        if($res['errcode']==0){
            return true;
        }else{
            $this->setError($res);
            return false;
        }
    }

}
?>