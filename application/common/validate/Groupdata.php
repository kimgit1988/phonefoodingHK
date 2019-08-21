<?php
namespace app\common\validate;
use \think\Validate;
class Groupdata extends Validate
{
    protected $rule = [
        'title'   => ['require', 'unique:Groupdata,title', 'length:3,25'],
        'rules'  => ['require'],
    ];

    protected $message = [
        'title.require'  => '角色名稱必須填寫',
        'title.unique'   => '角色名稱已經存在',
        'title.length'   => '角色名稱必須大於3個字符小於25個字符',
        'rules.require'   => '權限必須存在',
    ];

    protected $scene = [
        'add'        => ['title', 'status', 'rules'],
        'edit'       => ['title', 'status', 'rules'],
 
    ];
}
