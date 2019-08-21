<?php
namespace app\common\validate;
use think\Validate;
class Commission extends Validate
{
   
    protected $rule = [
        'name'      => ['require'],
        'percent'   => ['require','float'],
        'startNum'  => ['require','number'],
        'endNum'    => ['require','number'],
        'disable'   => ['require','in:0,1,2'],
    ];
    protected $message = [
        'name.require'          => '佣金分組名稱必须填写',
        'percent.require'       => '佣金比例必须填写',
        'percent.float'         => '佣金比例必须數字組成',
        'startNum.require'      => '店鋪數量最小值必须填写',
        'startNum.number'       => '店鋪數量最小值必须數字組成',
        'endNum.require'        => '店鋪數量最大值必须填写',
        'endNum.number'         => '店鋪數量最大值必须數字組成',
        'disable.require'       => '狀態值缺失',
        'disable.in'            => '狀態值不正確',
    ];
    protected $scene = [
        'backadd'  => ['name','percent','startNum','endNum','disable'],
        'backedit' => ['name','percent','startNum','endNum','disable'],
    ];
}
