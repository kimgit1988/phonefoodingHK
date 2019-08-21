<?php
//配置文件
return [
    // application信息
    'application'               => [
        'app_name'     => 'mobile',
        'app_url'      => 'http://www.xxxx.com/plugins/',
        'description'  => '此模块描述信息',
        'version'      => '1.0.0',
        'author'       => 'kiyang',
        'author_url'   => 'http://www.xxxx.com/',
        'license'      => 'GPL',
    ],
    // URL设置
    'url_route_on'          => true,
    // Session设置
    'session'               => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => 'session_id',
        // SESSION 前缀
        'prefix'         => 'minishop',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],
    // 验证码设置
    'captcha'   =>  [
        'fontSize'    =>    30,    // 验证码字体大小
        'length'      =>    3,     // 验证码位数
        'useNoise'    =>    false, // 关闭验证码杂点
    ],
    // 多语言设置
    'lang_switch_on'        => true,   // 开启语言包功能
    'lang_list'             => ['zh-cn,zh-tw,en-us'], // 支持的语言列表
    // 模板设置
    'view_replace_str'      =>[
        'STATIC_PATH'=> __ROOT__.'/static', // 模板变量替换
        'ROOT_PATH'  => __ROOT__, // 模板变量替换
    ],
    // 图片上传白名单
    'upload_picture_mime'   => 'image/bmp,image/gif,image/jpeg,image/png', //允许上传的图片后缀
    // 文件上传白名单
    'upload_file_mime'      => 'image/bmp,image/gif,image/jpeg,image/png,application/zip,application/rar,application/x-tar,application/x-gzip,application/octet-stream,application/msword,application/vnd.ms-excel,text/plain,application/xml', //允许上传的文件后缀
    'Rootpath'=>__ROOT__,
];
