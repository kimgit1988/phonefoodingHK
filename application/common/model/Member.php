<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class Member extends Model
{
    protected $auto = ['disable'];
    protected $type = [
        'id'          => 'integer',
        'disable'     => 'integer',
    ];

    public function memberAdd(array $params)
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
        return $this->insertGetId($save);
    }

    public function memberEdit(array $params)
    {
        $save = array();
        $save['name']          = $params['name'];
        $save['number']        = $params['number'];
        $save['remark']        = isset($params['remark'])?$params['remark']:'';
        $save['disable']       = $params['disable'];
        $save['linkMans']      = isset($params['linkMans'])?$params['linkMans']:'';
        $save['isDelete']      = 0;
        $save['cCategory']     = $params['cCategory'];
        $save['cCategoryName'] = $params['cCategoryName'];
        if(isset($params['logoUrl'])){
            $save['logoUrl']    = $params['logoUrl'];
        }
        return $this->where('id',$params['id'])->update($save);
    }

    public function deleteMember($id)
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
