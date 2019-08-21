<?php
namespace app\common\validate;
use\think\Validate;
use \think\Db;
class User extends Validate {
    protected $rule = [
        'id'                => ['require', 'length:0,6'],
        'zid'               => ['require', 'length:0,6'],
        //这里的unique ，唯一性，第一个是数据库名字User，第二个是字段name,自动验证
        'name'              => ['require', 'unique:user', 'length:3,25'],
        'nick'              => ['require', 'length:3,25'],
        'email'             => ['require','email','max:100'],
        'mobile'            => ['mobile','max:30'],
        'password'          => ['require'],
          //这里的密码，确认密码，自动验证
        'repassword'        => ['confirm:password'],
        'contact_number'    => ['require', 'unique:user'],
        'status'            => ['in:0,1'],
        'commission'        => ['float'],
        'account_number'    => ['account','max:30'],
        'account_name'      => ['max:30'],
    ];


    protected $message = [
        'id.require'                => '用戶組必須填寫',
        'password.require'          => '請輸入密碼',
        'repassword.confirm'        => '確認密碼不正確',
        'zid.require'               => '缺少關鍵參數',
        'name.require'              => '用戶名稱必須填寫',
        'name.unique'               => '用戶名稱已存在',
        'name.length'               => '用戶名稱必須大於3個字符小於25個字符',
        'email.require'             => '電郵地址必須填寫',
        'email.email'               => '電郵格式不正確',
        'email.max'                 => '電郵地址長度必須小於100位',
        'mobile.mobile'             => '手機號格式不正確',
        'mobile.max'                => '手機號不能超過30位',
        'nick.require'              => '稱呼必須填寫',
        'nick.length'               => '稱呼必須大於3個字符小於25個字符',
        'uid.length'                => '用戶名稱必須大於0個字符小於6個字符',
        'contact_number.require'    => '請填寫餐廳編號',
        'contact_number.unique'     => '該餐廳標號已被使用',
        'status.in'                 => '狀態值不可用',
        'commission'                => '佣金比例不正確',
        'account_number.account'    => '銀行賬號有數字和空格組成',
        'account_number.max'        => '銀行賬號不能超過30位',
        'account_name.max'          => '開戶人姓名不能超過30位',
    ];

    protected $regex = [
        'mobile'    => '/^[\d\+]?\d+[\d\s\-]+\d+$/',
        'account'   => '/^\d+[\d\s]+\d+$/',
    ];

    //不同的场景验证的字段不同
    protected $scene = [
        'add'       => ['id', 'name', 'nick', 'password', 'repassword', 'status','email','mobile','commission','account_number','account_name'],
        'edit'      => ['zid','id', 'nick', 'status','email','account_number','account_name','repassword'],
        'proedit'   => ['zid', 'nick', 'password', 'repassword', 'status', 'email'],
        'register'  => ['name', 'password', 'repassword','contact_number','email'=>'require|email'],
        'reset'     => ['email'=>'require|email'],
        'addstaff'  => ['name', 'password', 'repassword','email'],
        'repass'    => ['password', 'repassword'],
        'review'    => ['name'=>'require|checkHasValue:name|length:3,25','contact_number'=>'require|checkHasValue:contact_number'],
    ];

    protected function checkHasValue($value,$rule,$data)
    {   
        $res = Db::name('User')->where('zid','neq',$data['id'])->where($rule,$value)->find();
        if($res){
            if($rule=='contact_number'){
                return '該餐廳編號已被使用';
            }else if($rule=='name'){
                return '該用戶名稱已被使用';
            }
        }else{
            return true;
        }

    }

}