<?php
namespace app\common\validate;
use \think\Validate;
class Articlecat extends Validate
{
    protected $rule = [
        'parent_id'   => ['require'],
        'title'       => ['require','unique:Articlecat,title', 'max:100'],
        'keyword'     => ['max:100'],
        'description' => ['max:255'],
        'sort'        => ['require', 'integer'],
    ];
    protected $message = [
        'parent_id.require'  => '上機單頁面必須填寫',
        'title.require'      => '分類必須填寫',
        'title.require'      => '分類已經存在',
        'title.max'          => '分類長度不能超過10個字符',
        'keyword.max'        => '關鍵詞長度不能超過100個字符',
        'description.max'    => '簡介長度不能超過100個字符',
        'sort.require'       => '排序必須填寫',
        'sort.integer'       => '排序值不正確',
    ];
    protected $scene = [
        'add'  => ['parent_id', 'title', 'keyword', 'sort'],
        'edit' => ['parent_id', 'title', 'keyword', 'sort'],
    ];
}
