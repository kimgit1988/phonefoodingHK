<?php
namespace app\common\validate;
use \think\Validate;

class ContactDepartment extends Validate
{
    
    protected $rule = [
        'id'            => ['require','number'],
        'name'          => ['require','lengthnick'],
        'contactNumber' => ['require'],
        //'printerId'     => ['require','gt:0','number'],
        'disable'       => ['in:0,1'],
    ];

    protected $message = [
        'id.require'              => '頁面錯誤',
        'id.number'               => '頁面錯誤',
        'name.require'            => '部門名稱必須填寫',
        'name.lengthnick'         => '部門名稱必須大於2個字小於30個字',
        'contactNumber.require'   => '請選擇餐廳',
        //'printerId.require'       => '請選擇打印機',
        'printerId'               => '打印機的值不正確',
        'disable.in'              => '狀態選址不正確',
    ];

    protected $regex = [
        'lengthname' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,64}$/u',
        'lengthnick' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,30}$/u',
        'maxtwo'     => '/^[A-Za-z\x{4e00}-\x{9fa5}]{1,2}$/u',
    ];

    protected $scene = [
        'adminAdd'      => ['name','contactNumber','printerId','disable'],
        'adminEdit'     => ['id','name','contactNumber','printerId','disable'],
    ];

}
