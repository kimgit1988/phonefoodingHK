<?php
namespace app\common\validate;
use think\Validate;
class Cardinfo extends Validate
{
   
    protected $rule = [
        'id'                   => ['require','number','gt:0'],
        'name'                 => ['require','max:20'],
        'notice'               => ['max:30'],
        'cardCount'            => ['require','number','gt:0'],
        'useType'              => ['require','in:1,2'],
        'contactNumber'        => ['requireIf:useType,2'],
        'cardType'             => ['require','in:1,2,3'],
        'minDiscountPaid'      => ['requireIf:cardType,2','requireIf:cardType,3','float','egt:0'],
        'discountRate'         => ['requireIf:cardType,1','number','between:1,99'],
        // 滿減cardType=2或指定商品券cardType=3必填,折扣券cardType=1不填
        'discountMoney'        => ['requireIf:cardType,2','requireIf:cardType,3'],
        'maxDiscountRateMoney' => ['float','egt:0'],
        'receiptStartTime'     => ['require','date'],
        'receiptEndTime'       => ['require','date'],
        'distributeType'       => ['require','in:1,2,3,4'],
        'distributeUrl'        => ['requireIf:distributeType,1'],
        'distributeMoney'      => ['requireIf:distributeType,2','float','egt:0'],
        'timeType'             => ['require','in:1,2,3'],
        'timeLength'           => ['requireIf:timeType,1'],
        'validStartTime'       => ['requireIf:timeType,2','date'],
        'validEndTime'         => ['requireIf:timeType,2','date'],
        'limitNumber'          => ['require','number','gt:0'],
        'status'               => ['require','in:0,1']
    ];
    protected $message = [
        // 只設置名稱不設置規則所有該名稱規格全部使用該提示
        'id'                        => '頁面錯誤',
        'name.require'              => '請輸入卡券名',
        'name.max'                  => '卡券名少於20個字',
        'notice.max'                => '提示少於30個字',
        'cardCount.require'         => '請輸入卡券總數',
        'cardCount'                 => '卡券總數的值不正確',
        'useType.require'           => '請選擇可用餐廳',
        'useType.in'                => '可用餐廳的值不正確',
        'contactNumber.requireIf'   => '請選擇指定餐廳',
        'cardType.require'          => '請選擇優惠券類型',
        'cardType.in'               => '優惠券類型的值不正確',
        'minDiscountPaid.requireIf' => '請輸入最低使用金額',
        'minDiscountPaid'           => '最低使用金額值不正確',
        'discountRate.requireIf'    => '請輸入折扣率',
        'discountRate'              => '折扣率不正確',
        'discountMoney.requireIf'   => '請輸入減免金額',
        'maxDiscountRateMoney'      => '封頂金額的值不正確',
        'receiptStartTime.require'  => '請選擇領取開始時間',
        'receiptStartTime.date'     => '領取開始時間不正確',
        'receiptEndTime.require'    => '請選擇領取結束時間',
        'receiptEndTime.date'       => '領取結束時間不正確',
        'distributeType.require'    => '請選擇卡券派發方式',
        'distributeType'            => '卡券派發方式的值不正確',
        'distributeUrl.requireIf'   => '請輸入跳轉地址',
        'distributeMoney.requireIf' => '請輸入起派金額',
        'distributeMoney'           => '起派金額不正確',
        'timeType.require'          => '請選擇有效期類型',
        'timeType'                  => '有效期類型的值不正確',
        'timeLength.require'        => '請輸入有效時長',
        'validStartTime.requireIf'  => '請選擇有效期開始時間',
        'validStartTime'            => '有效期開始時間不正確',
        'validEneTime.requireIf'    => '請選擇有效期結束時間',
        'validEneTime'              => '有效期結束時間不正確',
        'limitNumber.require'       => '請輸入用戶領取次數',
        'limitNumber'               => '用戶領取次數不正確',
        'status.require'            => '請選擇狀態',
        'status'                    => '狀態不正確',

    ];
    protected $scene = [
        'adminAdd'  => ['name','notice','cardCount','useType','contactNumber','cardType','minDiscountPaid','discountRate','discountMoney','maxDiscountRateMoney','receiptStartTime','receiptEndTime','distributeType','distributeUrl','distributeMoney','timeType','timeLength','validStartTime','validEneTime','limitNumber','status'],
        'adminEdit' => ['id','name','notice','useType','contactNumber','cardType','minDiscountPaid','discountRate','discountMoney','maxDiscountRateMoney','receiptStartTime','receiptEndTime','distributeType','distributeUrl','distributeMoney','timeType','timeLength','validStartTime','validEneTime','limitNumber','status'],
    ];
}
