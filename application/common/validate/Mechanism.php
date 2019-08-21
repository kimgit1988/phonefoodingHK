<?php
namespace app\common\validate;
use think\Validate;
class Mechanism extends Validate
{
   
    protected $rule = [
        'name'          => ['require'],
        'commissionId'  => ['require','number'],
        'commission'    => ['require','float'],
        'disable'       => ['require','in:0,1,2'],
    ];
    protected $message = [
        'name.require'          => '機構名稱必须填写',
        'commissionId.require'  => '佣金分組必须填写',
        'commissionId.number'   => '佣金分組錯誤',
        'commission.require'    => '佣金分組錯誤',
        'commission.float'      => '佣金分組錯誤',
        'disable.require'       => '狀態值缺失',
        'disable.in'            => '狀態值不正確',
    ];
    protected $scene = [
        'backadd'  => ['name','commissionId','commission','disable'],
        'backedit' => ['name','commissionId','commission','disable'],
    ];
}
