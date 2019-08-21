<?php
namespace app\common\validate;
use think\Validate;
class Bank extends Validate
{
   
    protected $rule = [
        'bankname'  => ['require'],
        'bankcode'  => ['require','alphaNum'],
    ];
    protected $message = [
        'bankname.require'        => '銀行名稱必须填写',
        'bankcode.require'        => '銀行編號必须填写',
        'bankcode.alphaNum'       => '銀行編號只能由字母和數字組成',

    ];
    protected $scene = [
        'add'  => ['bankname','bankcode'],
        'edit' => ['bankname','bankcode'],
    ];
}
