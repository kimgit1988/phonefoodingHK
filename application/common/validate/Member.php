<?php
namespace app\common\validate;
use \think\Validate;

class Member extends Validate
{
    
    protected $rule = [
        'id'         => ['require','number'],
        'name'       => ['require', 'length:2,40'],
        'number'     => ['require'],
        'contactNumber'  => ['require'],
        'cCategory'  => ['require'],
        'disable'    => ['in:0,1'],
    ];

    protected $message = [
        'id.require'        => 'id获取失敗',
        'id.number'         => 'id值錯誤',
        'name.require'      => '餐枱必須填寫',
        'name.length'       => '餐枱必須大於2個字符小於40個字符',
        'number.require'    => '餐枱號必須填寫',
        'cCategory.require' => '請選擇餐枱類型',
        'cCategory.require' => '請選擇餐廳',
        'disable.in'        => '狀態選值不正確',
    ];

    protected $scene = [
        'add'        => ['name','number', 'cCategory', 'disable'],
        'edit'       => ['id','name','number', 'contactNumber', 'disable'],
    ];

}
