<?php
namespace app\common\validate;
use \think\Validate;

class FocusPosition extends Validate
{
    
    protected $rule = [
        'code' => ['require', 'alphaDash', 'length:3,25', 'unique:focus_position,code'],
        'name' => ['require', 'length:3,25'],
    ];
    protected $message = [
        'code.require'   => '調用代碼必須填寫',
        'code.alphaDash' => '調用代碼只能為字母和數字，下劃線_及破折號-',
        'code.length'    => '調用代碼長度3-25之間',
        'code.unique'    => '調用代碼已存在',
        'name.require'   => '調用名稱必須填寫',
        'name.length'    => '調用名稱長度3-25之間',
    ];
    protected $scene = [];
}
