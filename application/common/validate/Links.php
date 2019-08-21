<?php
namespace app\common\validate;
use \think\Validate;

class Links extends Validate
{
    protected $rule = [
        'title'  => ['require','unique:Links,title', 'length:2,25'],
        'url'    => ['require', 'url'],
        'linker' => ['max:255'],
        'status' => ['in:0,1'],
        'sort'   => ['require', 'integer'],

    ];

    protected $message = [
    
        'title.require' => '標題必須填寫',
        'title.length'  => '標題長度必須在2-25個字符之間',
        'url.require'   => '友情鏈接必須填寫',
        'url.url'       => '友情鏈接必須是一個網址',
        'status.in'     => '狀態值不正確',
        'sort.integer'  => '排序必須是一個整數',
    ];

    protected $scene = [
        'add'  => ['title', 'url', 'linker', 'status', 'sort'],
        'edit' => ['title', 'url', 'linker', 'status', 'sort'],
    ];
}
