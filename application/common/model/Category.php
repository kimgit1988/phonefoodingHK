<?php
namespace app\common\model;
use think\Model;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use think\Redirect;

class Category extends Model {

    // 餐厅方法
    public function parent()
    {
       return $this->hasMany('Category', 'parentId', 'id')->where(array('isDelete' => 0,'typeNumber'=>'customertype'))->order('ordnum asc');
    }

    /**
     * [index description]
     * @author ki_shang<923410459@qq.com>
     * @dateTime 2018-06
     * @return [type] [description]
    */
    public function CustomertypeAdd(array $params) {
        $save = array();
        $save['name']         = $params['name'];
        $save['level']        = $params['level'];
        $save['status']       = $params['status'];
        $save['remark']       = $params['remark'];
        $save['parentId']     = $params['parentId'];
        $save['typeNumber']   = 'customertype';
        $save['ordnum']       = isset($params['sort'])?$params['sort']:25;
        if(session('ext_user.is_contact')==0){
            $save['contactNumber'] = '';
        }else{
        // 商家可以看到自己创建的分类
            $save['contactNumber'] = session('ext_user.contact_number');
        }
        return $this->insertGetId($save);
    }

  /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function CustomertypeEdit(array $params) {
        $save = array();
        $save['name']         = $params['name'];
        $save['level']        = $params['level'];
        $save['status']       = $params['status'];
        $save['remark']       = $params['remark'];
        $save['parentId']     = $params['parentId'];
        $save['ordnum']       = isset($params['sort'])?$params['sort']:25;
        // 管理员修改不改变创建商户 所有只需要判断若为商户时 是否为该商户创建
        if(session('ext_user.is_contact')==1){
            $contactNumber = session('ext_user.contact_number');
            return $this->where('contactNumber',$contactNumber)->where('id',$params['id'])->update($save);
        }else{
            return $this->where('id',$params['id'])->update($save);
        }
        
    }

    // 餐桌方法
    public function parentc()
    {
       return $this->hasMany('Category', 'parentId', 'id')->where(array('isDelete' => 0,'typeNumber'=>'contactmember'))->order('ordnum asc');
    }

    public function ContactmemberAdd(array $params) {
        $save = array();
        $save['name']         = $params['name'];
        $save['level']        = $params['level'];
        $save['status']       = $params['status'];
        $save['remark']       = $params['remark'];
        $save['parentId']     = $params['parentId'];
        $save['typeNumber']   = 'contactmember';
        $save['ordnum']       = isset($params['sort'])?$params['sort']:25;
        if(session('ext_user.is_contact')==0){
            $save['contactNumber'] = '';
        }else{
        // 商家可以看到自己创建的分类
            $save['contactNumber'] = session('ext_user.contact_number');
        }
        return $this->insertGetId($save);
    }

  /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function ContactmemberEdit(array $params) {
        $save = array();
        $save['name']         = $params['name'];
        $save['level']        = $params['level'];
        $save['status']       = $params['status'];
        $save['remark']       = $params['remark'];
        $save['parentId']     = $params['parentId'];
        $save['ordnum']       = isset($params['sort'])?$params['sort']:25;
        // 管理员修改不改变创建商户 所有只需要判断若为商户时 是否为该商户创建
        if(session('ext_user.is_contact')==1){
            $contactNumber = session('ext_user.contact_number');
            return $this->where('contactNumber',$contactNumber)->where('id',$params['id'])->update($save);
        }else{
            return $this->where('id',$params['id'])->update($save);
        }
        
    }

    // 菜品分类方法
    public function parents()
    {
       return $this->hasMany('Category', 'parentId', 'id')->where(array('isDelete' => 0,'typeNumber'=>'trade'))->order('ordnum asc');
    }

    /**
     * [index description]
     * @author ki_shang<923410459@qq.com>
     * @dateTime 2018-06
     * @return [type] [description]
    */
    public function TradeAdd(array $params) {
        $save = array();
        $save['name']          = $params['name'];
        $save['level']         = $params['level'];
        $save['status']        = $params['status'];
        $save['remark']        = $params['remark'];
        $save['parentId']      = $params['parentId'];
        $save['typeNumber']    = 'trade';
        $save['ordnum']        = isset($params['sort'])?$params['sort']:25;
        $save['contactNumber'] = $params['contact'];
        return $this->insertGetId($save);
    }

  /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function TradeEdit(array $params) {
        $save = array();
        $save['name']          = $params['name'];
        $save['level']         = $params['level'];
        $save['status']        = $params['status'];
        $save['remark']        = $params['remark'];
        $save['parentId']      = $params['parentId'];
        $save['ordnum']        = isset($params['sort'])?$params['sort']:25;
        $save['contactNumber'] = $params['contact'];
        return $this->where('id',$params['id'])->update($save);
    }
    /**
     * 删除是3个共用的
     */
    public function deleteAttr($id) {
        $delete['isDelete'] = 1;
        return $this->where('id',$id)->update($delete);
    }

}