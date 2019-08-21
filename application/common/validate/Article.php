<?php
namespace app\common\validate;
use think\Validate;
class Article extends Validate
{
   
    protected $rule = [
        'category_id'      => ['require'],
        'title'            => ['require','unique:Article,title','max:255'],
        'keyword'          => ['max:100'],
        'attr'           => ['require', 'integer'],
        'thumbnail' => ['max:255'],
        'description'      => ['max:255'],
    ];

    protected $message = [
        'category_id.require'  => '分類必須填寫',
        'title.require'        => '標題必須填寫',
        'title.unique'        => '標題已經存在',
        'title.max'            => '標題長度不能超過100個字符',
        'keyword.max'          => '關鍵詞長度不能超過100個字符',
        'description.max'      => '簡介長度不能超過100個字符',
        'attr.require'      => '文章屬性必須選擇',
    ];

    protected $scene = [
        'add'  => ['category_id', 'title', 'keyword', 'attr'],
        'edit' => ['category_id', 'title', 'keyword', 'attr'],
    ];
}
