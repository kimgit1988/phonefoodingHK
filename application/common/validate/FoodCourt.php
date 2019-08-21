<?php
namespace app\common\validate;
use \think\Validate;
use think\Db;

class FoodCourt extends Validate
{
    
    protected $rule = [
        'id'            => ['require', 'number'],
        'name'          => ['require', 'length:2,40'],
        'number'        => ['require', 'unique:food_court', 'unique:contact', 'alphaNum','max:40'],
        'member'        => ['require', 'number'],
        'cCategory'     => ['require'],
        'contactType'   => ['require','number'],
        'disable'       => ['in:0,1,2,3'],
        'linkMans'      => ['regex:/^[\d\+]?\d+[\d\s\-]+\d+$/','max:30'],
        'rate'          => ['float'],
        'cycle'         => ['require','number','egt:0'],
        'remark'        => ['max:90'],
        'address'       => ['require','max:80'],
        'latitude'      => ['require','float'],
        'longitude'     => ['require','float'],
        'logoUrl'       => ['require'],
        'bgImageUrl'    => ['require'],
        'account_number'=> ['account','max:30'],
        'account_name'  => ['max:30'],
    ];

    protected $message = [
        'id.require'            => 'id獲取失敗',
        'id.number'             => 'id值錯誤',
        'name.require'          => '美食廣場名必須填寫',
        'name.length'           => '美食廣場名必须大於2個字符小於40個字符',
        'number.require'        => '美食廣場编号必须填写',
        'number.unique'         => '该美食廣場编号已被使用',
        'number.max'            => '该美食廣場编号不能超過40位',
        'number.alphaNum'       => '美食廣場编号必须由數字和字母組成',
        'member.require'        => '餐枱數必填',
        'member.number'         => '餐枱數錯誤',
        'cCategory.require'     => '請選擇美食廣場分類',
        'contactType.require'   => '請選擇美食廣場平台',
        'contactType.number'    => '美食廣場平台不正確',
        'disable.in'            => '狀態選值不正確',
        'rate.require'          => '請輸入費率',
        'rate.float'            => '费率的值為兩位小數',
        'cycle.require'         => '請輸入結算週期',
        'cycle.number'          => '結算週期為正整數',
        'cycle.egt'             => '結算週期為正整數',
        'remark.max'            => '備註不能超過90個字',
        'linkMans.max'          => '聯繫方式不能超過30位',
        'linkMans.regex'        => '聯繫方式不正確',
        'address.require'       => '請輸入美食廣場地址',
        'address.max'           => '美食廣場地址不能超過80字符',
        'latitude.require'      => '獲取美食廣場經緯度失敗',
        'latitude.float'        => '經緯度失敗值不正確',
        'longitude.require'     => '獲取美食廣場經緯度失敗',
        'longitude.float'       => '經緯度失敗值不正確',
        'logoUrl'               => '請上傳logo圖片',
        'bgImageUrl'            => '請上傳bg圖片',
        'account_number.max'    => '銀行賬號不能超過30位',
        'account_name.max'      => '開戶人姓名不能超過30位',
    ];

    protected $regex = [
        'account'    => '/^\d+[\d\s]+\d+$/',
    ];

    protected $scene = [
        'adminAdd'   => ['name','number', 'cCategory','member','contactType','disable','linkMans','rate'=>'require|float','cycle','remark','address','latitude','longitude','logoUrl','bgImageUrl'],
        'adminEdit'  => ['id','name', 'cCategory','contactType','disable','linkMans','rate'=>'require|float','cycle','remark','address','latitude','longitude'],
        'review'     => ['name','number'=>'require|checkHasValue:number', 'cCategory','member','disable'],
        'add'        => ['name','number', 'cCategory','member','contactType','disable','linkMans','account_number','rate'=>'require|float','cycle','remark','account_name','address','latitude','longitude'],
        'edit'       => ['id','name','linkMans','address','latitude','longitude'],
    ];

    protected function checkHasValue($value,$rule,$data)
    {   
        $res1 = Db::name('FoodCourt')->where('id','neq',$data['id'])->where($rule,$value)->find();
        $res2 = Db::name('Contact')->where($rule,$value)->find();
        if($res1||$res2){
            return '该美食廣場编号已被使用';
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
