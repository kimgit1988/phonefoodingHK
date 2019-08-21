<?php
namespace app\common\validate;
use \think\Validate;

class Goods extends Validate
{
    
    protected $rule = [
        'id'         => ['require','number'],
        'name'       => ['require', 'lengthname'],
        'payType'    => ['require','in:1,2'],
        'payUnit'    => ['requireIf:payType,2','maxtwo'],
        'salePrice'  => ['require','float'],
        'number'     => ['require'],
        'categoryId' => ['require'],
        'disable'    => ['in:0,1'],
        'sort'       => ['number', 'between:0,255'], 
    ];

    protected $message = [
        'id.require'         => 'id获取失敗',
        'id.number'          => 'id值錯誤',
        'name.require'       => '菜式必須填寫',
        'name.lengthname'    => '菜式名必須大於1個字符小於100個字符',
        'salePrice.require'  => '菜式單價必須填寫',
        'payType.require'    => '請選擇計價方式',
        'payType'            => '計價方式不正確',
        'payUnit.requireIf'  => '請輸入稱重單位',
        'payUnit.maxtwo'     => '稱重單位只能是英文和中文且不能超過兩個字',
        'salePrice.float'    => '菜式單價必須是數字',
        'number.require'     => '菜式編號必須填寫',
        'categoryId.require' => '請選擇菜式分類',
        'disable.in'         => '狀態選值不正確',
        'sort.number'        => '排序只能是一個數字',
        'sort.between'       => '排序範圍值只能在0-255之間',
    ];

    protected $regex = [
        'lengthname' => '/^[\s\S\x{4e00}-\x{9fa5}]{1,100}$/u',
        'maxtwo'     => '/^[A-Za-z\x{4e00}-\x{9fa5}]{1,2}$/u',
    ];

    protected $scene = [
        'add'       => ['name','payType','payUnit','number', 'salePrice', 'categoryId', 'disable', 'sort'],
        'set'       => ['id','name','payType','payUnit', 'salePrice', 'categoryId', 'disable', 'sort'],
        'edit'      => ['id','name','payType','payUnit', 'salePrice', 'categoryId', 'disable', 'sort'],
    ];

}
