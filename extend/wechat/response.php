<?php
/**
* 功能说明：
* 微信自动回复类，实现关键词回复，链接跳转；普通关注/扫码关注，取消关注，图片，多媒体，地图,群发和模板消息的回调等。
* @author zwf 
* @version 1.0
*/
require_once('core.php');
class Response extends Core{
 
    /**
    * 构造函数
    * @author 郑伟锋
    */
    public function __construct($config = array()) {
        //默认为zwf123456，实际根据用户填写
        parent::__construct($config);
    }
    /**
     * 消息真实性验证和自动回复入口
     * @author 郑伟锋 20170206
     */
    public function valid()
    {   //$echoStr = $_GET['echostr'];
      
        $echoStr = $_GET['echostr'];
        if($this->checkSignature()){

          //   echo $echoStr;
          // die;
          $this->responseMsg();
          exit;
        }
    }
    /**
     * 消息真实性验证
     * @author 郑伟锋 20170206
     */
    private function checkSignature()
    {
        $signature =$_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        //$signature = $_GET['signature'];
        //$timestamp = $_GET['timestamp'];
        //$nonce = $_GET['nonce'];

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
    
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 自动回复类型处理
     * @author 郑伟锋 20170206
     */
    public function responseMsg()
    {   
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        
         if (!empty($postStr)){
                
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $msgType =  $postObj->MsgType;

                switch ($msgType) {
                    //发送事件触发
                    case 'event':
                        $event = $postObj->Event;
                        switch ($event) {
                            //未关注时候关注事件
                            case 'subscribe':
                                //二维码关注
                                if(isset($postObj->EventKey) && isset($postObj->Ticket)){
                                    $tableid = ltrim($postObj->EventKey,"qrscene_table");
                                    $time         = time();
                                    $endtime      = $time+1200;
                                    $sql ="select * from ".$GLOBALS['db']->DB_PREFIX."_wx_reply where `type`=4 order by `id` desc limit 1";
                                    $list =$GLOBALS['db']->one($sql);
                                    if($list['reply']==1){
                                        echo $this->responseText($postObj,$list['msg']);
                                    }else{
                                        $news_content= array(
                                            array(
                                                'title'=>$tableid.'号桌点击开始点餐',
                                                'description'=>$list['msg'],
                                                'picurl'=>$list['pic'],
                                                'url'=>'http://tbmg.zhmicroera.com?tablenum='.$tableid.'&time='.$endtime
                                            ),
                                        );
                                        echo $this->responseNews($postObj,$news_content,$tableid);
                                    }
                                    // $writer['r'] = 'https://mmbiz.qpic.cn/mmbiz_jpg/tISxN7hC6ALoSaAeEDhYdTB1EKvayicprMgwD2JoNw574IQBlZsUQXmaXfDlia0z0WuuTLUjiaBUlxg2g4PIiawwFQ/0?wx_fmt=jpeg';
                                    // file_put_contents('test99.txt', json_encode($writer));
                                    
                                    die;
                                //普通关注
                                }else{
                                    $sql ="select * from ".$GLOBALS['db']->DB_PREFIX."_wx_reply where `type`=1 order by `id` desc limit 1";
                                    $list =$GLOBALS['db']->one($sql);
                                    if($list['reply']==1){
                                        echo $this->responseText($postObj,$list['msg']);
                                    }else{
                                        $news_content= array(
                                            array(
                                                'title'=>'',
                                                'description'=>$list['msg'],
                                                'picurl'=>$list['pic'],
                                                'url'=>'http://tbmg.zhmicroera.com?tablenum='
                                            ),
                                        );
                                        echo $this->responseNews($postObj,$news_content);
                                    }
                                    die;
                                }
                                break;
                            //已关注时候事件
                            case 'SCAN':
                                $tableid = ltrim($postObj->EventKey,"table");
                                $time         = time();
                                $endtime      = $time+1200;
                                $sql ="select * from ".$GLOBALS['db']->DB_PREFIX."_wx_reply where `type`=4 order by `id` desc limit 1";
                                $list =$GLOBALS['db']->one($sql);
                                if($list['reply']==1){
                                    echo $this->responseText($postObj,$list['msg']);
                                }else{
                                    
                                    $news_content= array(
                                        array(
                                            'title'=>$tableid.'号桌点击开始点餐',
                                            'description'=>$list['msg'],
                                            'picurl'=>$list['pic'],
                                            'url'=>'http://tbmg.zhmicroera.com?tablenum='.$tableid.'&time='.$endtime
                                        ),
                                    );
                                    echo $this->responseNews($postObj,$news_content,$tableid);
                                }
                                die;
                                break;
                            case 'CLICK':
                                if(isset($postObj->EventKey)){
                                    $key = $postObj->EventKey;
                                    $sql ="select * from ".$GLOBALS['db']->DB_PREFIX."_wx_menu where `key`='".$key."' order by `id` desc limit 1";
                                    $list =$GLOBALS['db']->one($sql);
                                    if(!empty($list)){
                                        echo $this->responseText($postObj,$list['msg']);
                                    }else{
                                        echo $this->responseText($postObj,'未查询到回复内容');
                                    }
                                }else{
                                    echo $this->responseText($postObj,'未知事件'.json_encode($postObj));
                                }
                            die;
                            break;
                            default:
                                echo $this->responseText($postObj,'未知事件'.json_encode($postObj));
                                die;
                                break;
                        }
                        break;
                    //发送文本关键词触发
                    case 'text':
                        //这里可以进行数据库关键词查询匹配要回复的内容
                        $key = $postObj->Content;
                        $sql ="select * from ".$GLOBALS['db']->DB_PREFIX."_wx_reply where `key`='".$key."' and `type`=3 order by `id` desc limit 1";
                        $list =$GLOBALS['db']->one($sql);
                        if(!empty($list)){
                            if($list['reply']==1){
                                echo $this->responseText($postObj,$list['msg']);
                            }else{
                                $news_content= array(
                                    array(
                                        'title'=>'',
                                        'description'=>$list['msg'],
                                        'picurl'=>$list['pic'],
                                        'url'=>'http://tbmg.zhmicroera.com?tablenum='
                                    ),
                                );
                                echo $this->responseNews($postObj,$news_content);
                            }
                        }else{
                            $sql ="select * from ".$GLOBALS['db']->DB_PREFIX."_wx_reply where `type`=2 order by `id` desc limit 1";
                            $list =$GLOBALS['db']->one($sql);
                            if($list['reply']==1){
                                echo $this->responseText($postObj,$list['msg']);
                            }else{
                                $news_content= array(
                                    array(
                                        'title'=>'',
                                        'description'=>$list['msg'],
                                        'picurl'=>$list['pic'],
                                        'url'=>'http://tbmg.zhmicroera.com?tablenum='
                                    ),
                                );
                                echo $this->responseNews($postObj,$news_content);
                            }
                        }
                        
                        
                        die;
                        break;
                    //发送图片触发
                    case 'image':
                        echo $this->responseImage($postObj,'0Sb9T8cuV1WtsinGOvNRnLIpGNhMOImnI4I1kceqd14');
                        die;
                        break;
                    //发送音频触发
                    case 'voice':
                        echo $this->responseVoice($postObj,'0Sb9T8cuV1WtsinGOvNRnKmacG5ac_BI-QbNkO0ZQ84');
                        die;
                        break;
                    //发送视频触发
                    case 'video':
                        echo $this->responseText($postObj,'video');
                        die;
                        break;
                    //发送小视频触发
                    case 'shortvideo':
                        echo $this->responseText($postObj,'shortvideo');
                        die;
                        break;
                    //发送地理位置触发
                    case 'location':
                        echo $this->responseText($postObj,'location');
                        die;
                        break;
                    //暂时不知道什么方式触发
                    case 'link':
                        echo $this->responseText($postObj,'link');
                        die;
                        break;
                    default:
                        echo $this->responseText($postObj,json_encode($postObj));
                        die;
                        break;
                }
               
        }else {
          echo "";
          exit;
        }
    }
 
    /**
     * 文本回复
     * @author 郑伟锋 20170206
     *$postObj 微信回调xml数据，$contentStr要回复的文本内容
     */
    public function responseText($postObj,$contentStr){
        $fromUsername = $postObj->FromUserName;
        $toUsername   = $postObj->ToUserName;
        $time         = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0</FuncFlag>
            </xml>";  
        $msgType = "text";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
        return $resultStr;
    }
    /**
     * 图片回复
     * @author 郑伟锋 20170206
     *$postObj 微信回调xml数据，$contentStr要回复的media_id
     */
    public function responseImage($postObj,$contentStr){
        $fromUsername = $postObj->FromUserName;
        $toUsername   = $postObj->ToUserName;
        $time         = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Image>
            <MediaId><![CDATA[%s]]></MediaId>
            </Image>
            </xml>";  

        $msgType = "image";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
        return $resultStr;
    }
    /**
     * 语音回复
     * @author 郑伟锋 20170206
     *$postObj 微信回调xml数据，$contentStr要回复的media_id
     */
    public function responseVoice($postObj,$contentStr){
        $fromUsername = $postObj->FromUserName;
        $toUsername   = $postObj->ToUserName;
        $time         = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Voice>
            <MediaId><![CDATA[%s]]></MediaId>
            </Voice>
            </xml>";  

        $msgType = "voice";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
        return $resultStr;
    }
    /**
     * 图文回复错误
     * @author 郑伟锋 20170206
     *$postObj 微信回调xml数据，$content要回复的图文数组
     */
   /* public function responseNews($postObj,$content){
        $fromUsername = $postObj->FromUserName;
        $toUsername   = $postObj->ToUserName;
        $time         = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>%s</Articles>
            </xml>";
        if(!empty($content)){
            foreach ($content as $key => $value) {
               $new .= "<item>
                <Title><![CDATA[$value['title']]></Title>
                <Description><![CDATA[$value['description']]></Description>
                <PicUrl><![CDATA[$value['picurl']]></PicUrl>
                <Url><![CDATA[$value['url']]></Url>
                </item>";
            }
        }

        $msgType = "news";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType,count($content),$new);
        return $resultStr;
    }
   */
    public function responseNews($postObj,$content,$scenc=""){
        $fromUsername = $postObj->FromUserName;
        $toUsername   = $postObj->ToUserName;
        $itemTpl = "<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>";
        $new = '';
        if(!empty($content)){
            foreach ($content as $key => $value) {
               $new.=sprintf($itemTpl, $value['title'], $value['description'], $value['picurl'], $value['url']);
            }
        }
          $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>$new</Articles>
            </xml>";
        $msgType = "news";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType,count($content));
        return $resultStr;
    }

}
?>