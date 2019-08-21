<?php
namespace app\common\validate;
use \think\Validate;

class PrinterBrand extends Validate
{
    
    protected $rule = [
        'id'          => ['require','number'],
        'brand'       => ['require', 'lengthname'],
        'brandNumber' => ['require', 'lengthname'],
        'type'        => ['require','in:1,2'],
        'fileName'    => ['require', 'lengthfile'],
        'disable'     => ['in:0,1'],
    ];

    protected $message = [
        'id.require'             => '頁面錯誤',
        'id.number'              => '頁面錯誤',
        'brand.require'          => '打印機品牌必須填寫',
        'brand.lengthname'       => '打印機品牌必須大於2個字小於50個字',
        'brandNumber.require'    => '打印機型號必須填寫',
        'brandNumber.lengthname' => '打印機型號必須大於2個字小於50個字',
        'type.require'           => '請選擇打印機類型',
        'type.in'                => '打印機類型不正确',
        'fileName.require'       => '文件名必須填寫',
        'fileName.lengthfile'    => '文件名必須大於2個字小於32個字',
        'disable.in'             => '狀態選值不正確',
    ];

    protected $regex = [
        'lengthname' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,50}$/u',
        'lengthfile' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,32}$/u',
        'maxtwo'     => '/^[A-Za-z\x{4e00}-\x{9fa5}]{1,2}$/u',
    ];

    protected $scene = [
        'adminAdd'      => ['brand','brandNumber','type','fileName','disable'],
        'adminEdit'     => ['id','brand','brandNumber','type','fileName','disable'],
    ];

}
