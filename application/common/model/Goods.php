<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class Goods extends Model
{
    protected $auto = ['disable'];
    protected $type = [
        'id'          => 'integer',
        'disable'     => 'integer',
    ];

    public function goodsAdd(array $params)
    {
        $save = array();
        $save['name']          = $params['name'];
        $save['number']        = $params['number'];
        $save['salePrice']     = $params['salePrice'];
        $save['disable']       = $params['disable'];
        $save['isDelete']      = 0;
        $save['payType']       = isset($params['payType'])?$params['payType']:1;
        $save['sort']          = $params['sort'];
        $save['categoryId']    = $params['categoryId'];
        $save['categoryName']  = $params['categoryName'];
        $save['contactNumber'] = $params['contactNumber'];
        $save['detail']        = isset($params['detail'])?$params['detail']:'';
        $save['remark']        = isset($params['remark'])?$params['remark']:'';
        $save['imgUrl']        = isset($params['imgUrl'])?$params['imgUrl']:'';
        $save['departmentId']  = isset($params['department'])?$params['department']:'';
        $save['thumbnailUrl']  = isset($params['thumbnailUrl'])?$params['thumbnailUrl']:'';
        if($save['payType']==2){
            $save['payUnit']       = isset($params['payUnit'])?$params['payUnit']:1;
        }
        return $this->insertGetId($save);
    }

    public function goodsEdit(array $params)
    {
        $save = array();
        $save['name']          = $params['name'];
        $save['salePrice']     = $params['salePrice'];
        $save['disable']       = $params['disable'];
        $save['isDelete']      = 0;
        $save['payType']       = isset($params['payType'])?$params['payType']:1;
        $save['sort']          = isset($params['sort'])?$params['sort']:0;
        $save['categoryId']    = $params['categoryId'];
        $save['categoryName']  = $params['categoryName'];
        $save['detail']        = isset($params['detail'])?$params['detail']:'';
        $save['remark']        = isset($params['remark'])?$params['remark']:'';
        $save['departmentId']  = isset($params['department'])?$params['department']:'';
        if(isset($params['thumbnailUrl'])){
            $save['thumbnailUrl']  = $params['thumbnailUrl'];
        }
        if(isset($params['imgUrl'])){
            $save['imgUrl']    = $params['imgUrl'];
        }
        if($save['payType']==2){
            $save['payUnit']       = isset($params['payUnit'])?$params['payUnit']:1;
        }
        return $this->where('id',$params['id'])->update($save);
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
