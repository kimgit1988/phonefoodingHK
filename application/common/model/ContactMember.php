<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class ContactMember extends Model
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
        $save['isDelete']      = 0;
        $save['cCategory']     = $params['cCategory'];
        $save['cCategoryName'] = $params['cCategoryName'];
        $save['contactNumber'] = $params['contactNumber'];
        return $this->insertGetId($save);
    }

    public function memberEdit(array $params)
    {
        $save = array();
        $save['name']          = $params['name'];
        $save['number']        = $params['number'];
        $save['remark']        = isset($params['remark'])?$params['remark']:'';
        $save['disable']       = $params['disable'];
        $save['isDelete']      = 0;
        $save['contactNumber'] = $params['contactNumber'];
        // $save['cCategory']     = $params['cCategory'];
        // $save['cCategoryName'] = $params['cCategoryName'];
        return $this->where('id',$params['id'])->update($save);
    }

    public function deleteMember($id)
    {
        $delete['isDelete'] = 1;
        if(session('ext_user.is_contact')==0){
            return $this->where('id',$id)->update($delete);
        }else{
            // 商家可以看到自己创建的分类(或管理员建立的)
            $contact_number = session('ext_user.contact_number');
            return $this->where('id',$id)->where('contactNumber',$contact_number)->update($delete);
        }
          
    }
    

    // protected function getDisableAttr($value)
    // {
    //     $disable = [0 => '禁用', 1 => '启用'];
    //     return $disable[$value];
    // }

}
