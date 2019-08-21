<?php
/**
* 功能说明：模板消息
* @author zwf 
* @version 1.0
*/
namespace Wechat;

class Pay{


    public $config = array(
        'APPID'         =>'',
        'MCHID'         =>'',
        'KEY'           =>'',
        'APPSECRET'     =>'',
        'SSLCERT_PATH'  =>'cert/apiclient_cert.pem',
        'SSLKEY_PATH'   =>'cert/apiclient_key.pem',
    );
  	/**
    * 构造函数
    * @author 郑伟锋
    */
    public function __construct($config = array()) {
        $this->config['APPID'] = $config['appId'];
        $this->config['MCHID'] = $config['MCHID'];
        $this->config['KEY'] = $config['KEY'];
        $this->config['APPSECRET'] = $config['appSecret'];
        $this->config['SSLCERT_PATH'] = $config['SSLCERT_PATH'];
        $this->config['SSLKEY_PATH'] = $config['SSLKEY_PATH'];
    }
    //统一下单
    public function unifiedOrder($info=array()){
        $order = array('appid'=>$this->config['APPID'],
            'mch_id'=>$this->config['MCHID'],
            'nonce_str'=>$this->getNonceStr(),
            'appid'=>$this->config['APPID'],
        );
        $data=array_merge($order,$info);
        $sign = $this->makeSign($data);
        $data['sign'] = $sign;
        $xml = $this->ToXml($data);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//接收xml数据的文件
        $res = $this->postXmlCurl($xml, $url,false,30);
        return $this->toArray($res);
    }

    /**
     * 
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public  function getNonceStr($length = 32) 
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        } 
        return $str;
    }

     /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($data){
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string=http_build_query($data);
        $string=urldecode($string);

        //签名步骤二：在string后加入KEY
        $string_sign=$string."&key=".$this->config['KEY'];
        //签名步骤三：MD5加密
        $sign = md5($string_sign);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }

    /**
     * 输出xml字符
     * @throws WxPayException
    **/
    public function ToXml($data)
    {
        if(!is_array($data)|| count($data) <= 0)
        {
            throw new WxPayException("数组数据异常！");
        }
        
        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml; 
    }
     /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array       转换得到的数组
     */
    public function toArray($xml){   
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $result;
    }
    /**
     * 以post方式提交xml到对应的接口url
     * 
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    private static function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {       
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容本地没有指定curl.cainfo路径的错误
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else { 
            $error = curl_errno($ch);
            curl_close($ch);
            return $error;
        }
    }
     /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function getParameters($data){
  
        $time=time();
        // 组合jssdk需要用到的数据
        $sign=array(
            'appId'=>$this->config['APPID'], //appid
            'timeStamp'=>strval($time), //时间戳
            'nonceStr'=>$data['nonce_str'],// 随机字符串
            'package'=>'prepay_id='.$data['prepay_id'],// 预支付交易会话标识
            'signType'=>'MD5'//加密方式
        );
        // 生成签名
        $sign['paySign']=$this->makeSign($sign);
        return $sign;
        
    }
     /**
     * 订单查询
     * @return array jssdk需要用到的数据
     */
    public function orderquery($info){
        $order = array('appid'=>$this->config['APPID'],
            'mch_id'=>$this->config['MCHID'],
            'nonce_str'=>$this->getNonceStr(),
            
        );
       
        $data=array_merge($order,$info);
        $sign = $this->makeSign($data);
        $data['sign'] = $sign;
        $xml = $this->ToXml($data);
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';//接收xml数据的文件
        $res = $this->postXmlCurl($xml, $url,false,30);
        return $this->toArray($res);
    }
}
?>