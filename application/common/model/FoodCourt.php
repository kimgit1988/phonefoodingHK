<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class FoodCourt extends Model
{
    protected $auto = ['disable'];
    protected $type = [
        'id'          => 'integer',
        'disable'     => 'integer',
    ];

    public function courtEdit(array $params)
    {
        $save = array();
        $save['name']          = $params['name'];
        $save['remark']        = isset($params['remark'])?$params['remark']:'';
        $save['disable']       = $params['disable'];
        $save['linkMans']      = isset($params['linkMans'])?$params['linkMans']:'';
        $save['email']         = isset($params['email'])?$params['email']:'';
        $save['isDelete']      = 0;
        $save['utime']         = time();
        if(isset($params['logoUrl'])){
            $save['logoUrl']    = $params['logoUrl'];
        }
        if(isset($params['bgImageUrl'])){
            $save['bgImageUrl']    = $params['bgImageUrl'];
        }
        return $this->where('id',$params['id'])->update($save);
    }

    public function deleteCourt($id)
    {
        $delete['isDelete'] = 1;
        return $this->where('id',$id)->update($delete);  
    }
    
    // 保留原始状态数值
    protected function getCourtDisableAttr($value,$data) {
        $name = [0 => '待審批', 1 => '啟用', 2 => '拒絕', 3 => '禁用'];
        if(isset($name[$data['disable']])){
            $return = $name[$data['disable']];
        }else{
            $return = '未知';
        }
        return $return;
    }

    protected function getCodeAttr($value,$data) {
        $name = [0 => '未製作', 1 => '製作中', 2 => '製作完成', 3 => '已派發'];
        if(isset($name[$data['codeStatus']])){
            $return = $name[$data['codeStatus']];
        }else{
            $return = '未知';
        }
        return $return;
    }
}
