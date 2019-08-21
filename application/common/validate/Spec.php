<?php
namespace app\common\validate;
use think\Validate;
class Spec extends Validate
{
   
    protected $rule = [
        'id'            => ['require','number'],
        'spec_name'     => ['require','max:50'],
        'spec_order'    => ['number','between:0,9999'],
        'spec_pid'      => ['require','number'],
        'spec_disable'  => ['require','in:0,1,2'],
        'contactNumber' => ['require'],
    ];
    protected $message = [
        'id.require'            => '頁面錯誤',
        'id.number'             => '頁面錯誤',
        'spec_name.require'     => '規格名稱必填',
        'spec_name.max'         => '規格名稱不能超過50個字',
        'spec_order.number'     => '排序值為正整數',
        'spec_order.max'        => '排序值在0-9999之間',
        'spec_pid.require'      => '請選擇父級規格',
        'spec_pid.number'       => '父級規格值錯誤',
        'spec_disable.require'  => '請選擇規格狀態',
        'spec_disable.number'   => '狀態值不正確',
        'contactNumber.require' => '請選擇所屬餐廳',

    ];
    protected $scene = [
        'add'  => ['spec_name','spec_order','spec_pid','spec_disable','contactNumber'],
        'edit' => ['id','spec_name','spec_order','spec_pid','spec_disable','contactNumber'],
    ];

}
