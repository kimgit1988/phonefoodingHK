<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展配置文件
    'extra_config_list'      => ['database', 'validate'],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => true,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => '',
    // 默认语言
    'default_lang'           => 'zh-tw',
    //语言允许列表
    'lang_list'              => ['zh-tw','zh-cn','en-us'],
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 网站信息配置
    // +----------------------------------------------------------------------
    'web_title'              =>'豐富點智能點餐系統',

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '頁面錯誤！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------
'http_exception_template'    =>  [
    // 定义404错误的重定向页面地址
    404 =>  APP_PATH.'404.html',
],
    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],
	

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],

    'captcha'  => [
        'codeSet'  => '0123456789',
        // 是否画混淆曲线
        'useCurve' => false,
        // 验证码位数
        'length'   => 4,
    ],
    'Rootpath'=>__ROOT__,
    // smtp发送邮件配置
    'smtp_config'=> [
        'smtp_charset' => 'UTF-8',
        'smtp_host'    => 'smtp.163.com',
        'smtp_user'    => 'mealorderingsys@163.com',
        'smtp_pass'    => 'mealorder2018',
        'smtp_name'    => '豐富點',
    ],
    'Thumwidth'=>68,//缩略图最大宽度
    'Thumheight'=>68,//缩略图最大宽度
    'UploadSize' => [
        'logo'  => 10*1024*1024, //2M
        'img'  => 10*1024*1024, //2M
    ],
    'web_qrcode' => [
        'suffix' => '/index.php/wxweb/index/index',
    ],
    'user_type' => [
        'wechat'    => [
            'type'  => 1,
            'name'  => '微信用户',
        ],
        'alipay'    => [
            'type'  => 2,
            'name'  => '支付宝用户',
        ],
        // 其他用户(一般为测试用户)
        'default'   =>[
            'type'  => 9999,
            'name'  => '其他用户',
        ],
    ],
    // 餐厅类型
    'contact_type' => [
        '1' => [
            //餐厅的类型(和键值相同)
            'contact_type'  =>'1',
            // 可以登录该商家的用户类型
            'user_type'     =>[1,2,9999],
            // select等显示的名称
            'name'          =>'微信&支付寶',
            'icon'          =>[__ROOT__.'/static/assets/img/wechat.png',__ROOT__.'/static/assets/img/alipay.png'],
        ],
        '2' => [
            //餐厅的类型(和键值相同)
            'contact_type'  =>'2',
            // 可以登录该商家的用户类型
            'user_type'     =>[1,9999],
            // select等显示的名称
            'name'          =>'微信',
            'icon'          =>[__ROOT__.'/static/assets/img/wechat.png'],
        ],
        '3' => [
            //餐厅的类型(和键值相同)
            'contact_type'  =>'3',
            // 可以登录该商家的用户类型
            'user_type'     =>[2,9999],
            // select等显示的名称
            'name'          =>'支付寶',
            'icon'          =>[__ROOT__.'/static/assets/img/alipay.png'],
        ],
    ],
    'pay_method' => [
        '1'     =>  '微信支付',
        '2'     =>  '支付寶支付',
        '3'     =>  '網銀支付',
        '4'     =>  '快捷支付',
    ],
    'ad_position' => [
        '1'     => [
            'name' => '首页banner',
            'size' =>'750*450',
        ],
        '2'     => [
            'name' =>'订单页顶部',
            'size' =>'750*180',
        ],
    ],
    // QQ地图配置
    'QQLbs' =>  [
        'Key'=>'3OTBZ-HPBWJ-ZZBFD-FZLXA-NVROS-4ZBJG',
        'Url'=>'https://apis.map.qq.com/ws/geocoder/v1',
    ],
    // 微信公众号配置
    'wx_web_config'    => [
        // 通用所需配置
        'appId'        => 'wxfe9cbe1ba53f8f87', //'wx2a2ba32495e98e32',
        'appSecret'    => 'fb36ea12d4354a4f473f6a081b43c778', //'6d194019bd544b5066111655a0b28239',
        // 授权类型
        'scope'        => 'snsapi_base',//snsapi_userinfo 顯示授權 // snsapi_base 隱式授權
        'sign'         => 'wxweb',
        'token'        => 'hellojerry',
        // // ??
        // 'bz'           => '',
        // 支付所需配置
        'MCHID'        => '1433851502',
        'KEY'          => 'mushanhiotao23456qwert678asdwzcx',
        'SSLCERT_PATH' => __ROOT__.'../api/WxpayAPI/cert/apiclient_cert.pem',
        'SSLKEY_PATH'  => __ROOT__.'../api/WxpayAPI/cert/apiclient_key.pem',
    ],
    // 支付宝公众号配置
    'alipay_web_config'    => [
        // 通用所需配置
        'app_id'        => '', //'',
        'appSecret'    => '', //'6d194019bd544b5066111655a0b28239',
        'RSA_PRIVATE_KEY' => '',//私钥
        'ALIPAY_RSA_PBULIC_KEY'  => '',//公钥
    ],
];
