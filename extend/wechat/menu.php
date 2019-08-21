<?php
/**
* 功能说明：微信菜单类，实现菜单创建，这里不实现查和改主要是因为用后台统一发布的方式
* @author zwf 
* @version 1.0
*/
require_once('core.php');
class Menu extends Core{
	  
  	/**
    * 构造函数
    * @author 郑伟锋
    */
    public function __construct($config = array()) {
        //默认为zwf123456，实际根据用户填写
        parent::__construct($config);
    }

    /**
    * 菜单创建
    * @author 郑伟锋
    */
    public function createMenu($data){
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->getAccessToken();
        $res = json_decode($this->curl($url,$data),true);
        if($res['errcode']==0){
            return true;
        }else{
            //父类的错误参数为私有，提供一个方法设置
            $this->setError($res);
            return false;
        }
    }

}
?>