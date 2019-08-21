<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class Contact extends Model
{
    protected $auto = ['disable'];
    protected $type = [
        'id'          => 'integer',
        'disable'     => 'integer',
    ];

    public function contactAdd(array $params)
    {
        $save = array();
        $save['name']          = $params['name'];
        $save['number']        = $params['number'];
        $save['remark']        = isset($params['remark'])?$params['remark']:'';
        $save['disable']       = $params['disable'];
        $save['logoUrl']       = isset($params['logoUrl'])?$params['logoUrl']:'';
        $save['linkMans']      = isset($params['linkMans'])?$params['linkMans']:'';
        $save['isDelete']      = 0;
        $save['cCategory']     = $params['cCategory'];
        $save['cCategoryName'] = $params['cCategoryName'];
        $save['rate']          = $params['rate'];
        $save['cycle']         = $params['cycle'];
        return $this->insertGetId($save);
    }

    public function contactEdit(array $params)
    {
        $save = array();
        $save['name']          = $params['name'];
        $save['remark']        = isset($params['remark'])?$params['remark']:'';
        $save['disable']       = $params['disable'];
        $save['linkMans']      = isset($params['linkMans'])?$params['linkMans']:'';
        $save['isDelete']      = 0;
        $save['cCategory']     = $params['cCategory'];
        $save['cCategoryName'] = $params['cCategoryName'];
        $save['rate']          = $params['rate'];
        $save['cycle']         = $params['cycle'];
        $save['address']       = trim($params['address']);
        $save['latitude']      = trim($params['latitude']);
        $save['longitude']     = trim($params['longitude']);
        $save['laterPay']      = $params['laterPay'];
        if(isset($params['logoUrl'])){
            $save['logoUrl']    = $params['logoUrl'];
        }
        if(isset($params['printer'])){
            $save['printerId']    = $params['printer'];
        }

        if(isset($params['smprinter'])){
            $save['smprinterId']    = $params['smprinter'];
        }
        return $this->where('id',$params['id'])->update($save);
    }

    public function deleteContact($id)
    {
        $contact = DB::name('contact')->field('number')->where('id',$id)->find();
        if($contact['id']>43){
            //上线后的餐厅标记删除
            $delete['isDelete'] = 1;
            $goods = DB::name('goods')->where('contactNumber',$contact['number'])->update($delete);
            $member = DB::name('contact_member')->where('contactNumber',$contact['number'])->update($delete);
            $category = DB::name('category')->where('contactNumber',$contact['number'])->update($delete);
            return $this->where('id',$id)->update($delete);
        }else{
            //真删除
            $res = false;
            if(!empty($contact)) {
                //开启事务
                DB::startTrans();
                try{
                    $goods = DB::name('goods')->where('contactNumber',$contact['number'])->delete();
                    $member = DB::name('contact_member')->where('contactNumber',$contact['number'])->delete();
                    $category = DB::name('category')->where('contactNumber',$contact['number'])->delete();
                    $order = DB::name('wxOrder')->where('contactNumber',$contact['number'])->delete();
                    $ordergood = DB::name('wxOrderGoods')->where('contactNumber',$contact['number'])->delete();
                    $user = DB::name('user')->where('contact_number',$contact['number'])->delete();
                    $meal = DB::name('setMeal')->where('contactNumber',$contact['number'])->delete();
                    $spec = DB::name('spec')->where('contactNumber',$contact['number'])->delete();
                    $goodspec = DB::name('goodsSpec')->where('contactNumber',$contact['number'])->delete();
                    $printer = DB::name('printer')->where('contactNumber',$contact['number'])->update(['contactNumber'=>'']);
                    $this->where('id',$id)->delete();
                    $res = true;
                } catch (\Exception $e) {
                    $res = false;
                    // 回滚事务
                    Db::rollback();
                }
            }
            return $res;
        }
    }

    // 保留原始状态数值
    protected function getContactDisableAttr($value,$data) {
        $name = [0 => '待审批', 1 => '启用', 2 => '拒绝', 3 => '禁用'];
        if(isset($name[$data['disable']])){
            $return = $name[$data['disable']];
        }else{
            $return = '未知';
        }
        return $return;
    }

    protected function getCodeAttr($value,$data) {
        $name = [0 => '未制作', 1 => '制作中', 2 => '制作完成', 3 => '已派发'];
        if(isset($name[$data['codeStatus']])){
            $return = $name[$data['codeStatus']];
        }else{
            $return = '未知';
        }
        return $return;
    }
}
