<?php
namespace app\common\validate;
use think\Validate;
class Setmealcategory extends Validate
{
   
    protected $rule = [
        'id'                   => ['require','number','gt:0'],
        'mid'                  => ['require','number','gt:0'],
        'name'                 => ['require','maxname'],
        'categoryMaxNumber'    => ['require','number','gt:0'],
        'goodsMaxNumber'       => ['require','number','gt:0'],
        'sort'                 => ['require','number'],
    ];
    protected $message = [
        // 只設置名稱不設置規則所有該名稱規格全部使用該提示
        'id'                        => '頁面錯誤',
        'mid'                       => '頁面錯誤',
        'name.require'              => '請輸入分類名',
        'name.maxname'              => '分類名大於2個字少於20個字',
        'categoryMaxNumber.require' => '請輸入分類可選數',
        'categoryMaxNumber'         => '分類可選數的值不正確',
        'goodsMaxNumber'            => '單項最大數的值不正確',
        'sort'                      => '排序的值不正確',

    ];
    protected $regex = [
        'maxname' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,40}$/u',
        'maxremark' => '/^[\s\S\x{4e00}-\x{9fa5}]{1,50}$/u',
        'maxtext' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,65000}$/u',
    ];
    protected $scene = [
        'add'  => ['mid','name','categoryMaxNumber','goodsMaxNumber','sort'],
        'edit' => ['id','mid','name','categoryMaxNumber','goodsMaxNumber','sort'],
    ];
}
