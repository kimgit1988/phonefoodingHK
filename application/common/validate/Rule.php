<?php
namespace app\common\validate;
use \think\Validate;

class Rule extends Validate
{
    
    protected $rule = [
        'name'      => ['require', 'unique:rule,name', 'length:2,25'],
        'title'     => ['require', 'length:2,25'],
        'parent_id' => ['require'],
        'status'    => ['in:0,1'],
        'sort'      => ['number', 'between:0,255'], 
    ];

    protected $message = [
        'name.require'      => '權限&菜單名必須填寫',
        'name.unique'       => '權限&菜單名已經存在',
        'name.length'       => '權限&菜單名必須大於2個字符小於25個字符',
        'title.require'     => '權限菜單名稱必須填寫',
        'title.length'      => '權限菜單名稱必須大於2個字符小於25個字符',
        'parent_id.require' => '上級菜單必須填寫',
        'status.in'         => '是否菜單選值不正確',
        'sort.number'       => '排序只能是一個數字',
        'sort.between'      => '排序範圍值只能在0-255之間',
    ];

    protected $scene = [
        'add'        => ['name', 'title', 'parent_id', 'status', 'sort', 'class'],
        'edit'        => ['name', 'title', 'parent_id', 'status', 'sort', 'class'],
    ];

}
