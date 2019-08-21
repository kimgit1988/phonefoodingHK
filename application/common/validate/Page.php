<?php
namespace app\common\validate;
use \think\Validate;
class Page extends Validate
{
    protected $rule = [
        'parent_id'   => ['require'],
        'title'       => ['require', 'unique:Page,title','max:100'],
        'keyword'     => ['max:100'],
        'description' => ['max:255'],
        'sort'        => ['require', 'integer'],
    ];
    protected $message = [
    
        'parent_id.require'  => '上機單頁面必須填寫',
        'title.require'      => '標題必須填寫',
        'title.unique'       => '標題名已經存在',
        'title.max'          => '標題長度不能超過100個字符',
        'keyword.max'        => '關鍵詞長度不能超過100個字符',
        'sort.require'       => '排序必須填寫',
        'sort.integer'       => '排序值不正確',
    ];

    protected $scene = [
        'add'  => ['parent_id', 'title', 'keyword',  'sort'],
        'edit' => ['parent_id', 'title', 'keyword', 'sort'],
    ];
}
