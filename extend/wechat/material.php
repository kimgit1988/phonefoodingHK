<?php
/**
* 功能说明：微信菜单类，实现菜单创建，这里不实现查和改主要是因为用后台统一发布的方式
* @author zwf 
* @version 1.0
*/
namespace Wechat;
use wechat\core;
class Material extends Core{
	  
  	/**
    * 构造函数
    * @author 郑伟锋
    */
  	public function __construct($config = array()) {
        //默认为zwf123456，实际根据用户填写
        parent::__construct($config);
    }

    /**
    * 素材上传
    * @author 郑伟锋
    * @param $file = array('filename' => '/public/home/images/0.jpg',   'content-type' => 'image/jpeg','filelength' => '4989' //大小    
    *   );
    * 媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
    */
    public function uploadMaterial($file,$type){
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=".$this->getAccessToken()."&type=".$type;
        $real_path = "{$_SERVER['DOCUMENT_ROOT']}{$file}";
        //php5.5以上传文件的方法
        $data = array("media" => new \CURLFile($real_path));
        $res = json_decode($this->curl($url,$data),true);
        if(isset($res['errcode'])){
            $this->setError($res);
            return false;
        }else{
            return $res;
            
        }
    }

    public function addNews($data){
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=".$this->getAccessToken();
        $res = json_decode($this->curl($url,$data),true);
        if(isset($res['errcode'])){
            $this->setError($res);
            return false;
        }else{
            return $res;
            
        }
    }

}
?>