<?php
namespace app\common\validate;
use \think\Validate;

class Category extends Validate
{
    
    protected $rule = [
        'name'      => ['require', 'length:2,100'],
        'parentId' => ['require'],
        'status'    => ['in:0,1'],
        'sort'      => ['number', 'between:0,255'], 
    ];

    protected $message = [
        'name.require'      => '分類名必須填寫',
        'name.length'       => '分類名必須大於2個字符小於100個字符',
        'parentId.require'  => '上級分類必選',
        'status.in'         => '狀態選值不正確',
        'sort.number'       => '排序只能是一個數字',
        'sort.between'      => '排序範圍只能在0-255之間',
    ];

    protected $scene = [
        'add'        => ['name', 'parentId', 'status', 'sort'],
        'edit'       => ['name', 'parentId', 'status', 'sort'],
    ];

}
