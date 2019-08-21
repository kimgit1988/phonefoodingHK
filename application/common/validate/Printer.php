<?php
namespace app\common\validate;
use \think\Validate;

class Printer extends Validate
{
    
    protected $rule = [
        'id'            => ['require','number'],
        'deviceNick'    => ['require','lengthnick'],
        'deviceNumber'  => ['require','lengthname'],
        'shopNumber'    => ['requireIf:brandId,1','lengthname'],
        'apiKey'        => ['requireIf:brandId,1','lengthname'],
        'brandId'       => ['require'],
        'contactNumber' => ['require'],
        'disable'       => ['in:0,1'],
    ];

    protected $message = [
        'id.require'              => '頁面錯誤',
        'id.number'               => '頁面錯誤',
        'deviceNick.require'      => '打印機暱稱必須填寫',
        'deviceNick.lengthnick'   => '打印機暱稱必須大於2個字小於30個字',
        'deviceNumber.require'    => '打印機編號必須填寫',
        'deviceNumber.lengthnick' => '打印機編號必須大於2個字小於64個字',
        'shopNumber.require'      => '商戶編號必須填寫',
        'shopNumber.lengthnick'   => '商戶編號必須大於2個字小於64個字',
        'apiKey.require'          => 'api密鑰必須填寫',
        'apiKey.lengthnick'       => 'api密鑰必須大於2個字小於64個字',
        'brandId.require'         => '請選擇打印機型號',
        'contactNumber.require'   => '請選擇餐廳',
        'disable.in'              => '狀態選值不正確',
    ];

    protected $regex = [
        'lengthname' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,64}$/u',
        'lengthnick' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,30}$/u',
        'maxtwo'     => '/^[A-Za-z\x{4e00}-\x{9fa5}]{1,2}$/u',
    ];

    protected $scene = [
        'adminYAdd'     => ['deviceNick','deviceNumber','shopNumber','apiKey','brandId','contactNumber','disable'],
        'adminAdd'      => ['deviceNick','deviceNumber','brandId','contactNumber','disable'],
        'adminEdit'     => ['id','deviceNick','deviceNumber','shopNumber','apiKey','brandId','contactNumber','disable'],
    ];

}
