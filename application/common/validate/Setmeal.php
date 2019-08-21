<?php
namespace app\common\validate;
use think\Validate;
class Setmeal extends Validate
{
   
    protected $rule = [
        'id'                   => ['require','number','gt:0'],
        'name'                 => ['require','maxname'],
        'totlePrice'           => ['require','float'],
        'contactNumber'        => ['require'],
        'categoryId'           => ['require'],
        //'expiryTimeType'       => ['require','in:1,2,3'],
        'status'               => ['require','in:0,1'],
//        'startDate'            => ['requireIf:expiryTimeType,1'],
//        'endDate'              => ['requireIf:expiryTimeType,1'],
//        'startTime'            => ['requireIf:expiryTimeType,2'],
//        'endTime'              => ['requireIf:expiryTimeType,2'],
        'remark'               => ['maxremark'],
        'detail'               => ['maxtext'],
    ];
    protected $message = [
        // 只設置名稱不設置規則所有該名稱規格全部使用該提示
        'id'                        => '頁面錯誤',
        'name.require'              => '請輸入套餐名',
        'name.maxname'              => '套餐名大於2個字少於20個字',
        'totlePrice.require'        => '請輸入套餐價格',
        'totlePrice'                => '套餐價格不正確',
        'contactNumber.require'     => '請選擇餐廳',
        'categoryId.require'        => '請選擇分類',
//        'expiryTimeType.require'    => '請選擇有效期類型',
//        'expiryTimeType'            => '有效期类型不正确',
        'status.require'            => '請選擇狀態',
        'status'                    => '狀態不正確',
//        'startDate.requireIf'       => '請輸入開始時間',
//        'endDate.requireIf'         => '請輸入結束時間',
//        'startTime.requireIf'       => '請輸入開始時段',
//        'endTime.requireIf'         => '請輸入結束時段',
        'remark.maxremark'          => '備註不能超過50字',
        'detail.maxtext'            => '詳情太長',

    ];
    protected $regex = [
        'maxname' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,40}$/u',
        'maxremark' => '/^[\s\S\x{4e00}-\x{9fa5}]{1,50}$/u',
        'maxtext' => '/^[\s\S\x{4e00}-\x{9fa5}]{2,65000}$/u',
    ];
    protected $scene = [
        'adminAdd'  => ['name','totlePrice','contactNumber','status'],
        'adminEdit' => ['id','name','totlePrice','contactNumber','status'],
        'add'  => ['name','totlePrice','categoryId','status','remark','detail'],
        'edit' => ['id','name','totlePrice','categoryId','status','remark','detail'],
    ];
}
