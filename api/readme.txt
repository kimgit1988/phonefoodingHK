所有对外接口尽量写在index.php里面，然后再index.php里面再调用其他外面的php文件里的方法

==================对外接口================
index.php
   所有的对外api的入口全部写在index.php里面

Notify.php(wxPayNotify.php)
   这是用于接收微信支付的返回结果

==================内部程序================
conn.php 用于连接数据库
WxpayAPI文件夹，网上下载的微信接口封装，如果要更换公众号，记得查看文档WxpayAPI\doc\README.doc
pay.php系基于WxpayAPI对微信支付统一下单的处理再做一次封装