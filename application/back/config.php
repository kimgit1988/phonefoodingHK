<?php
//配置文件
return [
    "Css"   => __ROOT__."/static/",
	"Houtai"   => __ROOT__."/static/houtai/",
	"Webroot"=>__ROOT__."/index.php",
	"Url"=>__ROOT__."/index.php/back/",
	"Ueditor"=>__ROOT__."/ueditor/",
	'Rootpath'=>__ROOT__."/",
	'Thumwidth'=>68,//缩略图最大宽度
	'Thumheight'=>68,//缩略图最大宽度
	'Rootpath'=>__ROOT__,
	'wx_config' => [
		'appid'  => 'wx805dba7d2c7f0272',
		'secret' => '25b6977d4fbc7ea2acc27735b83705c0',
	],
	'view_replace_str'      =>[
	    'STATIC_PATH'=> __ROOT__.'/static', // 模板变量替换
	    'ROOT_PATH'  => __ROOT__, // 模板变量替换
	],
];
