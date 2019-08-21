<?php
namespace app\common\validate;
use \think\Validate;

class Focus extends Validate
{
    protected $rule = [
        'position_id' => ['require'],
        'focus_image' => ['require'],
        'title'       => ['require', 'length:2,100', 'unique:focus,title'],
        'url'         => ['require', 'length:2,255'],
        'status'      => ['in:0,1'],
        'sort'        => ['require', 'integer'],
    ];

    protected $message = [
        'position_id.require' => '位置必須填寫',
        'focus_image.require' => '必須上傳圖片',
        'url.require'         => 'url必須填寫',
        'url.length'          => 'url長度2-255個字符之間',
        'title.require'       => '標題必須填寫',
        'title.length'        => '標題長度3-100之間',
        'title.unique'        => '該焦點圖已存在',
        'status.in'           => '狀態之不正確',
        'sort.length'         => '排序必須填寫',
        'sort.integer'        => '排序必須是整數',
    ];

    protected $scene = [
        'add'  => ['position_id', 'title', 'url', 'status', 'sort'],
        'edit' => ['position_id', 'title', 'url', 'status', 'sort'],
    ];
}
