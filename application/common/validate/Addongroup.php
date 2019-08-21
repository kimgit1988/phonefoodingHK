<?php
namespace app\common\validate;
use \think\Validate;

class Addongroup extends Validate
{

    protected $rule = [
        'name'       => ['require', 'length:1,100'],
    ];

    protected $message = [
        'name.require'      => '跟餐分組名称必須填寫',
        'name.length'       => '跟餐分組名称必须小於100個字符',
    ];

    protected $scene = [
        'add'        => ['name'],
        'edit'       => ['name'],
    ];

}
