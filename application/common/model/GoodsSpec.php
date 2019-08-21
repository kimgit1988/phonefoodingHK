<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class GoodsSpec extends Model
{
    protected $auto = [];
    protected $type = [
        'id'            => 'integer',
    ];

    public function gsAdd(array $params)
    {
        return $this->saveAll($params);
    }

    public function gsEdit(array $params)
    {
        // 已有需要更新的id
        $updateid = array();
        // 菜品id
        $gs_good_id = array();
        foreach ($params as $key => $val) {
            if(!empty($val['id'])){
                $updateid[] = $val['id'];
            }
            if(!in_array($val['gs_good_id'], $gs_good_id)){
                $gs_good_id[] = $val['gs_good_id'];
            }
        }
        // 所以需要不再需要更新的id(被刪除了)
        if(!empty($updateid)){
            $this->where('isDelete',0)->where('id','not in',$updateid)->where('gs_good_id','in',$gs_good_id)->update(['isDelete'=>1]);
            // 沒有需要更新的 只有新增(已有的全部刪除了)
        }else{
            $this->where('isDelete',0)->where('gs_good_id','in',$gs_good_id)->update(['isDelete'=>1]);
        }
        return $this->saveAll($params);
    }

    // 将某个菜品的规格全部改为删除状态
    public function gsClear($good_id){
        return $this->where('isDelete',0)->where('gs_good_id',$good_id)->update(['isDelete'=>1]);
    }

    public function deleteGoods($id)
    {
        $delete['isDelete'] = 1;
        return $this->where('id',$id)->update($delete);
    }


    protected function getDisableAttr($value)
    {
        $disable = [0 => '禁用', 1 => '啟用'];
        return $disable[$value];
    }

}
