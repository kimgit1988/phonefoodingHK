<?php
namespace app\common\validate;
use \think\Validate;
use think\Db;

class Contact extends Validate
{
    protected $rule = [
        'id'            => ['require', 'number'],
        'name'          => ['require', 'length:2,40'],
        'number'        => ['require', 'unique:contact', 'unique:food_court', 'alphaNum','max:40'],
        'member'        => ['require', 'number'],
        'cCategory'     => ['require'],
        'contactType'   => ['require','number'],
        'disable'       => ['in:0,1,2,3'],
        'linkMans'      => ['regex:/^[\d\+]?\d+[\d\s\-]+\d+$/','max:30'],
        'account_number'=> ['account','max:30'],
        'account_name'  => ['max:30'],
        'rate'          => ['float'],
        'cycle'         => ['require','number','egt:0'],
        'remark'        => ['max:90'],
        'address'       => ['require','max:80'],
        'latitude'      => ['require','float'],
        'longitude'     => ['require','float'],
        'autoOrder'     => ['require','in:0,1'],
        'laterPay'      => ['require','in:0,1'],
        'secretKey'     => ['require','length:32','alphaNum'],
    ];

    protected $message = [
        'id.require'            => 'id获取失败',
        'id.number'             => 'id值错误',
        'name.require'          => '餐廳名必须填写',
        'name.length'           => '餐廳名必须大於2個字符小於40個字符',
        'number.require'        => '餐廳编号必须填写',
        'number.unique'         => '该餐廳编号已被使用',
        'number.max'            => '该餐廳编号不能超過40位',
        'number.alphaNum'       => '餐廳编号必须由數字和字母组成',
        'member.require'        => '餐檯數必填',
        'member.number'         => '餐檯數错误',
        'cCategory.require'     => '请选择餐廳分類',
        'contactType.require'   => '请选择支付平台',
        'contactType.number'    => '支付平台不正確',
        'disable.in'            => '狀態選擇不正確',
        'account_number.account'=> '銀行賬號由數字和空格組成',
        'rate.require'          => '請輸入費率',
        'rate.float'            => '费率的值為兩位小數',
        'cycle.require'         => '请输入結算週期',
        'cycle.number'          => '結算週期為正整數',
        'cycle.egt'             => '結算週期為正整數',
        'remark.max'            => '備註不能超過90個字',
        'linkMans.max'          => '聯繫方式不能超過30位',
        'linkMans.regex'        => '聯繫方式不正確',
        'account_number.max'    => '銀行賬號不能超過30位',
        'account_name.max'      => '開戶人姓名不能超過30位',
        'address.require'       => '请输入餐廳地址',
        'address.max'           => '餐廳地址不能超過80字符',
        'latitude.require'      => '獲取餐廳經緯度失敗',
        'latitude.float'        => '經緯度失敗值不正確',
        'longitude.require'     => '獲取餐廳經緯度失敗',
        'longitude.float'       => '經緯度失敗值不正確',
        'autoOrder.require'     => '請設置自動接單的值',
        'autoOrder.in'          => '自動接單的值不正確',
        'laterPay.require'      => '請設置自后支付的值',
        'laterPay.in'           => '后支付的值不正確',
        'secretKey.require'     => '請設置密鑰的值',
        'secretKey.length'      => '密鑰長度必須位32位',
        'secretKey.alphaNum'    => '密鑰由數字和字母組成',
    ];

    protected $regex = [
        'account'    => '/^\d+[\d\s]+\d+$/',
    ];

    protected $scene = [
        'add'        => ['name','number', 'cCategory','member','contactType','disable','linkMans','account_number','rate'=>'require|float','cycle','remark','account_name','address','latitude','longitude'],
        'edit'       => ['id','name','number', 'cCategory', 'disable','linkMans'],
        'reset'      => ['id','name', 'cCategory','rate'=>'require|float', 'disable','cycle','address','latitude','longitude','linkMans'],
        'contact'    => ['name','cCategory','linkMans','account_number','address','latitude','longitude'],
        'review'     => ['name','number'=>'require|checkHasValue:number', 'cCategory','member','disable'],
        'courtEdit'  => ['id','name','address','latitude','longitude'],
        'autoOrder'  => ['autoOrder'],
        'laterPay'   => ['laterPay'],
        'secretKey'  => ['secretKey'],
    ];

    protected function checkHasValue($value,$rule,$data)
    {
        $res1 = Db::name('Contact')->where('id','neq',$data['id'])->where($rule,$value)->find();
        $res2 = Db::name('FoodCourt')->where($rule,$value)->find();
        if($res1||$res2){
            return '该餐廳编号已被使用';
        }else{
            return true;
        }

    }

    protected function checkLinkMans($value,$rule,$data)
    {
        $preg = "/^[\d\+]?\d+[\d\s\-]+\d+$/";
        if(preg_match($preg,$value)){
            return true;
        }else{
            return "电话号码格式不正确";
        }
    }

}
