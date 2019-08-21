<?php
namespace app\common\validate;
use think\Validate;
class Attr extends Validate
{
   
    protected $rule = [
        'attr_name'  => ['require','unique:Attr,attr_name','max:20'],
    ];
    protected $message = [
        'attr_name.require'        => '文章屬性必須填寫',
        'attr_name.unique'        => '文章屬性已經存在',
        'attr_name.max'            => '屬性長度不能超過20個字符',

    ];
    protected $scene = [
        'add'  => ['attr_name'],
        'edit' => ['attr_name'],
    ];
}
