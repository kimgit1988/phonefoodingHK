<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/4/10
 * Time: 19:30
 */

namespace app\service;


class BrowserDetector
{
    private $_browserType = false;

    protected function checkBrowserType()
    {
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
            $this->_browserType = 'wechat';
        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Alipay') !== false){
            $this->_browserType = 'alipay';
        }else{
            $this->_browserType = 'default';
        }
    }

    public static function getBrowserType(){
        $instance = new static();
        $instance->checkBrowserType();
        $configBrowser = config('user_type');
        return $configBrowser[$instance->_browserType]['type'];
    }
}